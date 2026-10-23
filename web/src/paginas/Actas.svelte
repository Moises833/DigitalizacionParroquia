<script>
  import { onMount } from "svelte"
  import { pb, mensajeError } from "../lib/pb.js"
  import { ir } from "../lib/ruta.svelte.js"
  import { compararFolios } from "../lib/imagen.js"
  import { libroPreferido, recordarLibro } from "../lib/preferencias.js"
  import { prepararTokenArchivos, urlArchivo } from "../lib/archivos.svelte.js"

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
      const [lista, actas] = await Promise.all([
        pb.collection("paginas").getFullList({ filter: filtroLibro, fields: "id,collectionId,folio,estado,imagen,miniatura" }),
        pb.collection("actas_bautismo").getFullList({ filter: filtroLibro, fields: "pagina,estado" }),
      ])
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
  a.boton { text-decoration: none; white-space: nowrap; }
  .oculto { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }
</style>
