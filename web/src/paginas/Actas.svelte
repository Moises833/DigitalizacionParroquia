<script>
  import { onMount } from "svelte"
  import { pb, mensajeError } from "../lib/pb.js"
  import { ir } from "../lib/ruta.svelte.js"
  import { compararFolios } from "../lib/imagen.js"
  import { libroPreferido, recordarLibro } from "../lib/preferencias.js"
  import { prepararTokenArchivos, urlArchivo } from "../lib/archivos.svelte.js"
  import { estadoIA, iaDisponible, enviarAIA, posicionesSinLeer } from "../lib/ia.js"

  const ESTADOS = {
    pendiente: "Sin separar",
    segmentada: "Por transcribir",
    completa: "Completa",
  }

  let libros = $state([])
  let libroId = $state("")
  let paginas = $state([])
  let actasPorPagina = $state(new Map()) // pagina -> { total, revisadas }
  let filtro = $state("pendientes") // pendientes | todas | completas
  let error = $state("")
  let cargando = $state(false)

  // IA
  let ia = $state(null)
  let actasLibro = $state([]) // { pagina, posicion }
  let trabajosLibro = $state([]) // { id, pagina, posicion, estado }
  let progresoIA = $state("")

  const dePagina = (lista, id) => lista.filter((x) => x.pagina === id)
  // Actas que se pueden mandar a leer: recuadros sin acta ni trabajo en la cola.
  let porLeer = $derived(paginas
    .filter((p) => p.estado === "segmentada")
    .map((p) => ({ pagina: p, posiciones: posicionesSinLeer(p.segmentos ?? [], dePagina(actasLibro, p.id), dePagina(trabajosLibro, p.id)) }))
    .filter((x) => x.posiciones.length))
  let totalPorLeer = $derived(porLeer.reduce((s, x) => s + x.posiciones.length, 0))

  function resumenIA(paginaId) {
    const conActa = new Set(dePagina(actasLibro, paginaId).map((a) => a.posicion))
    const cuenta = { cola: 0, listo: 0, error: 0 }
    for (const t of dePagina(trabajosLibro, paginaId)) {
      if (conActa.has(t.posicion)) continue
      if (t.estado === "listo") cuenta.listo++
      else if (t.estado === "error") cuenta.error++
      else cuenta.cola++
    }
    return [
      cuenta.listo && `${cuenta.listo} leída${cuenta.listo > 1 ? "s" : ""} por la IA`,
      cuenta.cola && `${cuenta.cola} en cola`,
      cuenta.error && `${cuenta.error} con error`,
    ].filter(Boolean).join(" · ")
  }

  async function leerLibroConIA() {
    if (!confirm(`Se enviarán ${totalPorLeer} actas a la IA. ¿Continuar?`)) return
    error = ""
    try {
      let hechas = 0
      for (const { pagina, posiciones } of porLeer) {
        progresoIA = `Preparando folio ${pagina.folio} (${hechas} de ${totalPorLeer} actas enviadas)…`
        const enviados = await enviarAIA(pagina, urlArchivo(pagina, pagina.imagen), pagina.segmentos, posiciones,
          dePagina(trabajosLibro, pagina.id))
        trabajosLibro = [
          ...trabajosLibro.filter((t) => !enviados.some((e) => e.id === t.id)),
          ...enviados.map(({ id, pagina, posicion, estado }) => ({ id, pagina, posicion, estado })),
        ]
        hechas += enviados.length
      }
      progresoIA = `Listo: ${hechas} actas en la cola de la IA. Se leen solas, unas pocas por minuto.`
      ia = await estadoIA()
    } catch (err) {
      progresoIA = ""
      error = `No se pudo enviar a la IA: ${mensajeError(err)}`
    }
  }

  let ordenadas = $derived([...paginas].sort((a, b) => compararFolios(a.folio, b.folio)))
  let visibles = $derived(ordenadas.filter((p) =>
    filtro === "todas" || (filtro === "completas" ? p.estado === "completa" : p.estado !== "completa")))
  let siguiente = $derived(ordenadas.find((p) => p.estado !== "completa"))
  let resumen = $derived({
    paginas: paginas.length,
    completas: paginas.filter((p) => p.estado === "completa").length,
    actas: [...actasPorPagina.values()].reduce((s, a) => s + a.total, 0),
    borradores: [...actasPorPagina.values()].reduce((s, a) => s + a.total - a.revisadas, 0),
  })

  async function cambiarLibro(id) {
    libroId = id
    recordarLibro(id)
    error = ""
    cargando = true
    try {
      const filtroLibro = pb.filter("libro = {:l}", { l: id })
      const [lista, actas, trabajos, estado] = await Promise.all([
        pb.collection("paginas").getFullList({ filter: filtroLibro, fields: "id,collectionId,folio,estado,imagen,miniatura,segmentos" }),
        pb.collection("actas_bautismo").getFullList({ filter: filtroLibro, fields: "pagina,posicion,estado" }),
        pb.collection("trabajos_ia").getFullList({ filter: filtroLibro, fields: "id,pagina,posicion,estado" }),
        estadoIA().catch(() => null),
      ])
      actasLibro = actas
      trabajosLibro = trabajos
      ia = estado
      progresoIA = ""
      const conteo = new Map()
      for (const a of actas) {
        const c = conteo.get(a.pagina) ?? { total: 0, revisadas: 0 }
        c.total++
        if (a.estado !== "borrador") c.revisadas++
        conteo.set(a.pagina, c)
      }
      paginas = lista
      actasPorPagina = conteo
    } catch (err) {
      error = mensajeError(err)
    } finally {
      cargando = false
    }
  }

  const vista = (p) => p.miniatura ? urlArchivo(p, p.miniatura) : urlArchivo(p, p.imagen, { thumb: "400x0" })

  onMount(async () => {
    try {
      const [lista] = await Promise.all([
        pb.collection("libros").getFullList({ sort: "anio_inicio,numero" }),
        prepararTokenArchivos(),
      ])
      libros = lista
      const inicial = libros.find((l) => l.id === libroPreferido()) ?? libros[0]
      if (inicial) await cambiarLibro(inicial.id)
    } catch (err) {
      error = mensajeError(err)
    }
  })
</script>

<h1>Actas</h1>

{#if error}<div class="alerta error" role="alert">{error}</div>{/if}

{#if libros.length === 0}
  <div class="tarjeta">
    <p>Primero registre un libro y capture sus páginas.</p>
    <a class="boton" href="#/libros">Ir a Libros</a>
  </div>
{:else}
  <div class="fila barra">
    <div class="campo">
      <label for="libro-actas">Libro</label>
      <select id="libro-actas" value={libroId} onchange={(e) => cambiarLibro(e.currentTarget.value)}>
        {#each libros as l (l.id)}
          <option value={l.id}>{l.numero}{l.anio_inicio ? ` (${l.anio_inicio}–${l.anio_fin || "…"})` : ""}</option>
        {/each}
      </select>
    </div>
    {#if siguiente}
      <button class="boton" onclick={() => ir(`pagina/${siguiente.id}`)}>
        Continuar donde quedé (folio {siguiente.folio})
      </button>
    {/if}
  </div>

  <ul class="resumen" aria-label="Avance del libro">
    <li><strong>{resumen.completas}</strong> de {resumen.paginas} páginas completas</li>
    <li><strong>{resumen.actas}</strong> actas transcritas</li>
    <li><strong>{resumen.borradores}</strong> sin revisar</li>
  </ul>

  {#if iaDisponible(ia)}
    <div class="barra-ia">
      <span>IA: <strong>{ia.cola.pendiente + ia.cola.procesando}</strong> en cola ·
        <strong>{ia.cola.por_revisar}</strong> leídas, por revisar</span>
      {#if totalPorLeer}
        <button class="boton secundario" onclick={leerLibroConIA} disabled={!!progresoIA && !progresoIA.startsWith("Listo")}>
          Leer con IA las páginas separadas ({totalPorLeer} actas)
        </button>
      {/if}
      {#if progresoIA}<span class="suave" aria-live="polite">{progresoIA}</span>{/if}
    </div>
  {/if}

  {#if paginas.length === 0 && !cargando}
    <div class="tarjeta">
      <p>Este libro todavía no tiene páginas.</p>
      <a class="boton" href="#/captura">Ir a Captura de páginas</a>
    </div>
  {:else}
    <div class="pestanas" role="tablist" aria-label="Filtrar páginas">
      {#each [["pendientes", "Por terminar"], ["completas", "Completas"], ["todas", "Todas"]] as [valor, texto]}
        <button role="tab" aria-selected={filtro === valor} class:activa={filtro === valor}
                onclick={() => (filtro = valor)}>{texto}</button>
      {/each}
    </div>

    {#if visibles.length === 0}
      <p class="suave">{filtro === "pendientes" ? "Todas las páginas de este libro están completas." : "No hay páginas en esta vista."}</p>
    {:else}
      <div class="tabla-contenedor">
        <table class="tabla">
          <thead>
            <tr><th>Página</th><th>Folio</th><th>Estado</th><th>Actas</th><th><span class="oculto">Acción</span></th></tr>
          </thead>
          <tbody>
            {#each visibles as p (p.id)}
              {@const c = actasPorPagina.get(p.id)}
              <tr>
                <td><img class="mini" src={vista(p)} alt="" loading="lazy" /></td>
                <td><strong>{p.folio}</strong></td>
                <td><span class="estado {p.estado}">{ESTADOS[p.estado] ?? p.estado}</span></td>
                <td>
                  {c?.total ?? 0}
                  {#if c && c.total > c.revisadas}<span class="suave"> ({c.total - c.revisadas} sin revisar)</span>{/if}
                  {#if resumenIA(p.id)}<div class="ia-pagina">{resumenIA(p.id)}</div>{/if}
                </td>
                <td class="accion">
                  <a class="boton" class:secundario={p.estado === "completa"} href="#/pagina/{p.id}">
                    {p.estado === "completa" ? "Revisar" : "Transcribir"}
                  </a>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    {/if}
  {/if}
{/if}

<style>
  .barra { align-items: flex-end; margin-bottom: 1rem; }
  .barra .campo { margin-bottom: 0; max-width: 360px; }
  .resumen { list-style: none; padding: 0; margin: 0 0 1rem; display: flex; flex-wrap: wrap; gap: 0.5rem 1.5rem; }
  .resumen strong { font-size: 1.2rem; color: var(--primario); }

  .pestanas { display: inline-flex; border: 1px solid var(--borde); border-radius: var(--radio); overflow: hidden; margin-bottom: 1rem; }
  .pestanas button { min-height: 40px; padding: 0.4rem 1rem; border: 0; background: var(--superficie); cursor: pointer; }
  .pestanas button + button { border-left: 1px solid var(--borde); }
  .pestanas button.activa { background: var(--primario); color: #fff; font-weight: 600; }

  .mini { width: 48px; height: 64px; object-fit: cover; border-radius: 4px; border: 1px solid var(--borde); display: block; }
  .estado { font-size: 0.85rem; font-weight: 600; padding: 0.15rem 0.6rem; border-radius: 999px; white-space: nowrap; }
  .estado.pendiente { background: var(--fondo); color: var(--texto-suave); }
  .estado.segmentada { background: #fdf1dc; color: #7a4a0c; }
  .estado.completa { background: #e8f3ea; color: var(--exito); }
  .accion { text-align: right; }
  .barra-ia {
    display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 1rem; margin: -0.25rem 0 1rem;
    padding: 0.5rem 0.75rem; border-radius: var(--radio); background: #e9f0f8; color: #1d3f63;
  }
  .ia-pagina { font-size: 0.85rem; color: #1d3f63; }
  a.boton { text-decoration: none; white-space: nowrap; }
  .oculto { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }
</style>
