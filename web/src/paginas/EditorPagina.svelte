<script>
  import { onMount, onDestroy } from "svelte"
  import { pb, mensajeError } from "../lib/pb.js"
  import { ir } from "../lib/ruta.svelte.js"
  import { compararFolios } from "../lib/imagen.js"
  import { prepararTokenArchivos, urlArchivo } from "../lib/archivos.svelte.js"
  import { franjas, mismoRecorte, recortarImagen, MAX_SEGMENTOS } from "../lib/recorte.js"
  import LienzoSegmentos from "../componentes/LienzoSegmentos.svelte"
  import FormularioActa from "../componentes/FormularioActa.svelte"

  let { id } = $props()

  const ESPERA_GUARDADO = 700

  let pagina = $state(null)
  let libro = $state(null)
  let actas = $state([])
  let segmentos = $state([])
  let seleccionado = $state(0)
  let proporcion = $state(0.75) // alto/ancho de la foto, para mostrar el recorte
  let sugerencias = $state({ ministro: [], lugar_nacimiento: [] })
  let ultimaDelLibro = $state(null) // para copiar datos al empezar una página
  let sucio = $state(false)
  let estadoRecuadros = $state("") // "" | guardando | guardado | error
  let error = $state("")
  let cargando = $state(true)
  let temporizador = null

  let urlImagen = $derived(pagina ? urlArchivo(pagina, pagina.imagen) : "")
  let porPosicion = $derived(new Map(actas.map((a) => [a.posicion, a])))
  let conActa = $derived(new Set(actas.map((a) => a.posicion)))
  let posicion = $derived(seleccionado + 1)
  let completa = $derived(segmentos.length > 0 && segmentos.every((_, i) => conActa.has(i + 1)))
  let anterior = $derived(porPosicion.get(posicion - 1) ?? ultimaDelLibro)

  async function cargar() {
    cargando = true
    try {
      const [p, lista] = await Promise.all([
        pb.collection("paginas").getOne(id, { expand: "libro" }),
        pb.collection("actas_bautismo").getFullList({ filter: pb.filter("pagina = {:p}", { p: id }) }),
        prepararTokenArchivos(),
      ])
      pagina = p
      libro = p.expand?.libro ?? null
      actas = lista
      segmentos = Array.isArray(p.segmentos) ? p.segmentos : []
      const sinActa = segmentos.findIndex((_, i) => !lista.some((a) => a.posicion === i + 1))
      seleccionado = Math.max(0, sinActa)

      const [ministro, lugar, ultima] = await Promise.all([
        pb.send("/api/sugerencias/ministro", {}),
        pb.send("/api/sugerencias/lugar_nacimiento", {}),
        pb.collection("actas_bautismo").getList(1, 1, {
          filter: pb.filter("libro = {:l} && pagina != {:p}", { l: p.libro, p: id }),
          sort: "-created",
        }),
      ])
      sugerencias = { ministro, lugar_nacimiento: lugar }
      ultimaDelLibro = ultima.items[0] ?? null
    } catch (err) {
      error = err?.status === 404 ? "La página no existe o fue eliminada." : mensajeError(err)
    } finally {
      cargando = false
    }
  }

  // --- Recuadros ----------------------------------------------------------

  function programarGuardado() {
    clearTimeout(temporizador)
    estadoRecuadros = "guardando"
    temporizador = setTimeout(guardarRecuadros, ESPERA_GUARDADO)
  }

  async function guardarRecuadros() {
    temporizador = null
    try {
      const copia = $state.snapshot(segmentos)
      pagina = await pb.collection("paginas").update(id, { segmentos: copia })
      // Si se movió el recuadro de un acta ya guardada, se rehace su recorte.
      for (const acta of actas) {
        const s = copia[acta.posicion - 1]
        if (!s || mismoRecorte(acta.recorte, s)) continue
        const datos = new FormData()
        datos.append("recorte", JSON.stringify(s))
        datos.append("imagen_recorte", await recortarImagen(urlImagen, s))
        const nueva = await pb.collection("actas_bautismo").update(acta.id, datos)
        actas = actas.map((a) => (a.id === nueva.id ? nueva : a))
      }
      estadoRecuadros = "guardado"
    } catch (err) {
      estadoRecuadros = "error"
      error = `No se pudieron guardar los recuadros: ${mensajeError(err)}`
    }
  }

  function confirmarDescarte() {
    return !sucio || confirm("Hay cambios sin guardar en el acta. ¿Descartarlos?")
  }

  function puedeSeleccionar() {
    if (!confirmarDescarte()) return false
    sucio = false
    return true
  }

  function usarFranjas(cantidad) {
    segmentos = franjas(cantidad)
    seleccionado = 0
    programarGuardado()
  }

  function quitarUltimo() {
    if (!puedeSeleccionar()) return
    segmentos = segmentos.slice(0, -1)
    seleccionado = Math.min(seleccionado, Math.max(0, segmentos.length - 1))
    programarGuardado()
  }

  // --- Actas --------------------------------------------------------------

  function alGuardado(registro, seguir) {
    actas = porPosicion.has(registro.posicion)
      ? actas.map((a) => (a.id === registro.id ? registro : a))
      : [...actas, registro]
    ultimaDelLibro = registro
    error = ""
    if (!seguir) return
    // Siguiente recuadro sin acta, empezando por el de abajo.
    const n = segmentos.length
    for (let paso = 1; paso <= n; paso++) {
      const i = (seleccionado + paso) % n
      if (!conActa.has(i + 1)) {
        seleccionado = i
        return
      }
    }
    // Todas transcritas: se ofrece la página siguiente.
    queueMicrotask(() => document.getElementById("boton-siguiente")?.focus())
  }

  function alEliminado(acta) {
    actas = actas.filter((a) => a.id !== acta.id)
  }

  async function siguientePagina() {
    if (!confirmarDescarte()) return
    if (temporizador) {
      clearTimeout(temporizador)
      await guardarRecuadros()
    }
    try {
      const pendientes = await pb.collection("paginas").getFullList({
        filter: pb.filter("libro = {:l} && estado != 'completa' && id != {:p}", { l: pagina.libro, p: id }),
        fields: "id,folio",
      })
      pendientes.sort((a, b) => compararFolios(a.folio, b.folio))
      const destino = pendientes.find((p) => compararFolios(p.folio, pagina.folio) > 0) ?? pendientes[0]
      if (!destino) {
        error = ""
        alert("No quedan páginas por transcribir en este libro.")
        return ir("actas")
      }
      sucio = false
      ir(`pagina/${destino.id}`)
    } catch (err) {
      error = mensajeError(err)
    }
  }

  function antesDeSalir(evento) {
    if (sucio || temporizador) evento.preventDefault()
  }

  onMount(() => {
    cargar()
    window.addEventListener("beforeunload", antesDeSalir)
  })

  onDestroy(() => {
    window.removeEventListener("beforeunload", antesDeSalir)
    if (temporizador) {
      clearTimeout(temporizador)
      guardarRecuadros() // no se pierden los últimos ajustes al cambiar de página
    }
  })
</script>

{#if cargando}
  <p class="suave">Cargando página…</p>
{:else if !pagina}
  <div class="alerta error" role="alert">{error}</div>
  <a href="#/actas">Volver a Actas</a>
{:else}
  <div class="encabezado">
    <div>
      <a href="#/actas" class="volver">← Actas</a>
      <h1>{libro?.numero ?? "Libro"} · Folio {pagina.folio}</h1>
    </div>
    <span class="espaciador"></span>
    <button id="boton-siguiente" class="boton" class:secundario={!completa} onclick={siguientePagina}>
      Siguiente página por transcribir →
    </button>
  </div>

  {#if error}<div class="alerta error" role="alert">{error}</div>{/if}
  {#if completa}
    <div class="alerta exito" role="status">Todas las actas de esta página están transcritas.</div>
  {/if}

  <div class="editor">
    <section class="columna-pagina" aria-label="Página y recuadros de actas">
      <div class="herramientas">
        {#if actas.length === 0}
          <span>Actas en la página:</span>
          {#each [1, 2, 3, 4] as n}
            <button class="boton secundario chico" class:elegido={segmentos.length === n}
                    onclick={() => usarFranjas(n)}>{n}</button>
          {/each}
        {/if}
        <button class="boton secundario chico" onclick={quitarUltimo}
                disabled={segmentos.length === 0 || conActa.has(segmentos.length)}
                title={conActa.has(segmentos.length) ? "El último recuadro ya tiene un acta guardada" : ""}>
          Quitar último
        </button>
        <span class="estado-guardado suave" aria-live="polite">
          {#if estadoRecuadros === "guardando"}Guardando recuadros…
          {:else if estadoRecuadros === "guardado"}Recuadros guardados ✓
          {:else if estadoRecuadros === "error"}<span class="error-texto">Sin guardar</span>{/if}
        </span>
      </div>

      <LienzoSegmentos src={urlImagen} bind:segmentos bind:seleccionado {conActa} {puedeSeleccionar}
                       alCambiar={programarGuardado}
                       alCargar={(img) => (proporcion = img.naturalWidth / img.naturalHeight)} />

      <p class="ayuda suave">
        {#if segmentos.length === 0}
          Elija cuántas actas tiene la página o dibuje un recuadro arrastrando sobre la imagen.
        {:else}
          Arrastre los recuadros para ajustarlos a cada acta; los bordes cambian el tamaño.
          {#if segmentos.length < MAX_SEGMENTOS}Arrastre sobre una zona vacía para agregar otro.{/if}
        {/if}
      </p>
    </section>

    <section class="columna-acta tarjeta" aria-label="Datos del acta">
      {#if segmentos[seleccionado]}
        {#key `${id}-${seleccionado}-${porPosicion.get(posicion)?.id ?? "nueva"}`}
          <FormularioActa {pagina} {posicion} segmento={segmentos[seleccionado]}
                          acta={porPosicion.get(posicion) ?? null} {anterior}
                          {urlImagen} proporcionImagen={proporcion} {sugerencias}
                          bind:sucio {alGuardado} {alEliminado} />
        {/key}
      {:else}
        <p class="suave">Marque primero dónde está cada acta en la página.</p>
      {/if}
    </section>
  </div>
{/if}

<style>
  .encabezado { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; margin-bottom: 1rem; }
  .encabezado h1 { margin: 0; }
  .volver { font-size: 0.95rem; text-decoration: none; }

  .editor { display: grid; grid-template-columns: minmax(280px, 5fr) 7fr; gap: 1.25rem; align-items: start; }
  .columna-pagina { position: sticky; top: 1rem; }
  .columna-acta { padding: 1.25rem; min-width: 0; }

  .herramientas { display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center; margin-bottom: 0.6rem; }
  .chico { min-height: 34px; padding: 0.2rem 0.75rem; }
  .chico.elegido { background: var(--primario); color: #fff; }
  .estado-guardado { margin-left: auto; font-size: 0.85rem; }
  .error-texto { color: var(--error); font-weight: 600; }
  .ayuda { font-size: 0.9rem; margin: 0.5rem 0 0; }

  @media (max-width: 980px) {
    .editor { grid-template-columns: 1fr; }
    .columna-pagina { position: static; }
  }
</style>
