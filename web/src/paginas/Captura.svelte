<script>
  import { onMount, onDestroy } from "svelte"
  import { pb, mensajeError } from "../lib/pb.js"
  import { compararFolios } from "../lib/imagen.js"
  import { prepararTokenArchivos } from "../lib/archivos.svelte.js"
  import SubidaPC from "../componentes/SubidaPC.svelte"
  import VinculoMovil from "../componentes/VinculoMovil.svelte"
  import GaleriaPaginas from "../componentes/GaleriaPaginas.svelte"
  import VisorPagina from "../componentes/VisorPagina.svelte"

  const CLAVE_LIBRO = "captura.libro"

  let libros = $state([])
  let libroId = $state("")
  let paginas = $state([])
  let modo = $state("pc") // pc | movil
  let seleccionada = $state(null)
  let recientes = $state(new Set()) // ids recién llegados del teléfono, para resaltarlos
  let aviso = $state("")
  let error = $state("")
  let cancelarSuscripcion = null

  let libro = $derived(libros.find((l) => l.id === libroId))
  let ordenadas = $derived([...paginas].sort((a, b) => compararFolios(a.folio, b.folio)))
  let folioSugerido = $derived(
    paginas.reduce((max, p) => Math.max(max, parseInt(p.folio, 10) || 0), 0) + 1,
  )

  function leerPreferencia() {
    try { return localStorage.getItem(CLAVE_LIBRO) || "" } catch { return "" }
  }
  function guardarPreferencia(id) {
    try { localStorage.setItem(CLAVE_LIBRO, id) } catch { /* sin almacenamiento local */ }
  }

  async function cargarPaginas() {
    paginas = libroId
      ? await pb.collection("paginas").getFullList({ filter: pb.filter("libro = {:l}", { l: libroId }) })
      : []
  }

  async function suscribir() {
    if (cancelarSuscripcion) await cancelarSuscripcion()
    cancelarSuscripcion = null
    if (!libroId) return
    cancelarSuscripcion = await pb.collection("paginas").subscribe("*", (evento) => {
      const p = evento.record
      if (evento.action === "create") {
        if (!paginas.some((x) => x.id === p.id)) paginas = [...paginas, p]
        if (p.origen === "movil") {
          recientes = new Set([...recientes, p.id])
          aviso = `Llegó una foto del teléfono: folio ${p.folio}.`
        }
      } else if (evento.action === "update") {
        paginas = paginas.map((x) => (x.id === p.id ? p : x))
      } else if (evento.action === "delete") {
        paginas = paginas.filter((x) => x.id !== p.id)
      }
    }, { filter: pb.filter("libro = {:l}", { l: libroId }) })
  }

  async function cambiarLibro(id) {
    libroId = id
    guardarPreferencia(id)
    recientes = new Set()
    aviso = ""
    error = ""
    try {
      await Promise.all([cargarPaginas(), suscribir()])
    } catch (err) {
      error = mensajeError(err)
    }
  }

  onMount(async () => {
    const [lista] = await Promise.all([
      pb.collection("libros").getFullList({ sort: "anio_inicio,numero" }),
      prepararTokenArchivos(),
    ])
    libros = lista
    const preferido = leerPreferencia()
    const inicial = libros.find((l) => l.id === preferido) ?? libros[0]
    if (inicial) await cambiarLibro(inicial.id)
  })

  onDestroy(() => {
    cancelarSuscripcion?.()
  })
</script>

<h1>Captura de páginas</h1>

{#if libros.length === 0}
  <div class="tarjeta">
    <p>Primero registre al menos un libro.</p>
    <a class="boton" href="#/libros">Ir a Libros</a>
  </div>
{:else}
  <div class="fila barra">
    <div class="campo">
      <label for="libro">Libro</label>
      <select id="libro" value={libroId} onchange={(e) => cambiarLibro(e.currentTarget.value)}>
        {#each libros as l (l.id)}
          <option value={l.id}>{l.numero}{l.anio_inicio ? ` (${l.anio_inicio}–${l.anio_fin || "…"})` : ""}</option>
        {/each}
      </select>
    </div>
    <div class="pestanas" role="tablist" aria-label="Forma de captura">
      <button role="tab" aria-selected={modo === "pc"} class:activa={modo === "pc"} onclick={() => (modo = "pc")}>
        Subir desde esta PC
      </button>
      <button role="tab" aria-selected={modo === "movil"} class:activa={modo === "movil"} onclick={() => (modo = "movil")}>
        Usar el teléfono
      </button>
    </div>
  </div>

  {#if aviso}<div class="alerta exito" role="status">{aviso}</div>{/if}
  {#if error}<div class="alerta error" role="alert">{error}</div>{/if}

  {#if libro}
    {#key libroId}
      {#if modo === "pc"}
        <SubidaPC {libro} {folioSugerido} foliosOcupados={paginas.map((p) => p.folio)} />
      {:else}
        <VinculoMovil {libro} {folioSugerido} />
      {/if}
    {/key}

    <GaleriaPaginas paginas={ordenadas} {recientes} titulo="Páginas de {libro.numero}"
                    alAbrir={(p) => (seleccionada = p)} />
  {/if}
{/if}

{#if seleccionada}
  <VisorPagina pagina={seleccionada} foliosOcupados={paginas.map((p) => p.folio)}
               alCerrar={() => (seleccionada = null)} />
{/if}

<style>
  .barra { align-items: flex-end; margin-bottom: 1rem; }
  .barra .campo { margin-bottom: 0; max-width: 360px; }
  .pestanas { display: flex; border: 1px solid var(--borde); border-radius: var(--radio); overflow: hidden; }
  .pestanas button {
    min-height: 42px; padding: 0.5rem 1rem; border: 0; background: var(--superficie); cursor: pointer;
  }
  .pestanas button + button { border-left: 1px solid var(--borde); }
  .pestanas button.activa { background: var(--primario); color: #fff; font-weight: 600; }
  a.boton { text-decoration: none; }
</style>
