<script>
  import { onMount } from "svelte"
  import { pb, mensajeError } from "../lib/pb.js"
  import { formatearFecha } from "../lib/fechas.js"
  import { construirFiltro, palabras, resaltar, FILTROS_VACIOS } from "../lib/busqueda.js"

  const CLAVE = "busqueda.filtros"
  const POR_PAGINA = 30
  const ESPERA = 250
  const ORDENES = {
    nombre: { texto: "Nombre", sort: "apellidos,nombres,fecha_bautizo" },
    fecha: { texto: "Fecha de bautizo", sort: "fecha_bautizo,apellidos,nombres" },
    reciente: { texto: "Registradas recientemente", sort: "-created" },
  }

  function leerGuardados() {
    try {
      return { filtros: { ...FILTROS_VACIOS }, orden: "nombre", ...JSON.parse(sessionStorage.getItem(CLAVE) || "{}") }
    } catch {
      return { filtros: { ...FILTROS_VACIOS }, orden: "nombre" }
    }
  }
  const guardados = leerGuardados()

  let filtros = $state(guardados.filtros)
  let orden = $state(guardados.orden)
  let libros = $state([])
  let resultados = $state([])
  let total = $state(0)
  let pagina = $state(1)
  let buscando = $state(false)
  let error = $state("")
  let temporizador = null
  let consulta = 0 // descarta respuestas de búsquedas anteriores

  let criterio = $derived(construirFiltro(filtros))
  let hayCriterio = $derived(!!criterio.expresion)
  let resaltadas = $derived({
    nombre: palabras(filtros.nombre),
    padres: palabras(filtros.padres),
    padrinos: palabras(filtros.padrinos),
  })

  async function buscar(mas = false) {
    const numero = ++consulta
    error = criterio.error
    if (criterio.error) {
      resultados = []
      total = 0
      buscando = false
      return
    }
    buscando = true
    try {
      const siguiente = mas ? pagina + 1 : 1
      const r = await pb.collection("actas_bautismo").getList(siguiente, POR_PAGINA, {
        filter: criterio.expresion ? pb.filter(criterio.expresion, criterio.parametros) : "",
        sort: hayCriterio ? ORDENES[orden].sort : "-created",
        expand: "libro,pagina",
        fields: "id,nombres,apellidos,sexo,fecha_bautizo,fecha_nacimiento,padre,madre,padrino,madrina,numero_acta,estado,posicion,pagina,libro,expand.libro.numero,expand.pagina.folio",
      })
      if (numero !== consulta) return
      resultados = mas ? [...resultados, ...r.items] : r.items
      total = r.totalItems
      pagina = siguiente
    } catch (err) {
      if (numero === consulta) error = mensajeError(err)
    } finally {
      if (numero === consulta) buscando = false
    }
  }

  // Busca mientras se escribe, con una pequeña espera para no consultar por cada tecla.
  function cambio() {
    try { sessionStorage.setItem(CLAVE, JSON.stringify({ filtros, orden })) } catch { /* sin almacenamiento */ }
    clearTimeout(temporizador)
    temporizador = setTimeout(() => buscar(false), ESPERA)
  }

  function limpiar() {
    filtros = { ...FILTROS_VACIOS }
    cambio()
    document.getElementById("buscar-nombre")?.focus()
  }

  const personas = (a, b) => [a, b].filter(Boolean).join(" y ")

  onMount(async () => {
    try {
      libros = await pb.collection("libros").getFullList({ sort: "anio_inicio,numero" })
    } catch (err) {
      error = mensajeError(err)
    }
    buscar(false)
  })
</script>

{#snippet marcado(texto, buscadas)}
  {#each resaltar(texto, buscadas) as t}{#if t.marcado}<mark>{t.texto}</mark>{:else}{t.texto}{/if}{/each}
{/snippet}

<h1>Buscar actas</h1>

<form class="tarjeta filtros" oninput={cambio} onchange={cambio} onsubmit={(e) => { e.preventDefault(); buscar(false) }} role="search">
  <div class="rejilla">
    <div class="campo ancho">
      <label for="buscar-nombre">Nombre del bautizado</label>
      <!-- svelte-ignore a11y_autofocus -->
      <input id="buscar-nombre" bind:value={filtros.nombre} autocomplete="off" spellcheck="false" autofocus
             placeholder="Nombres o apellidos, en cualquier orden" />
    </div>
    <div class="campo">
      <label for="buscar-padres">Padres</label>
      <input id="buscar-padres" bind:value={filtros.padres} autocomplete="off" spellcheck="false" placeholder="Padre o madre" />
    </div>
    <div class="campo">
      <label for="buscar-padrinos">Padrinos</label>
      <input id="buscar-padrinos" bind:value={filtros.padrinos} autocomplete="off" spellcheck="false" placeholder="Padrino o madrina" />
    </div>
    <div class="campo">
      <label for="buscar-libro">Libro (tomo)</label>
      <select id="buscar-libro" bind:value={filtros.libro}>
        <option value="">Todos</option>
        {#each libros as l (l.id)}<option value={l.id}>{l.numero}</option>{/each}
      </select>
    </div>
    <div class="campo">
      <label for="buscar-anio">Año de bautizo</label>
      <input id="buscar-anio" bind:value={filtros.anio} autocomplete="off" spellcheck="false" placeholder="1981 o 1980-1985"
             aria-invalid={!!criterio.error} />
    </div>
    <div class="campo chico">
      <label for="buscar-folio">Folio</label>
      <input id="buscar-folio" bind:value={filtros.folio} autocomplete="off" spellcheck="false" maxlength="20" />
    </div>
    <div class="campo chico">
      <label for="buscar-numero">N.º de acta</label>
      <input id="buscar-numero" bind:value={filtros.numero} autocomplete="off" spellcheck="false" maxlength="20" />
    </div>
  </div>
  <div class="fila pie-filtros">
    <div class="campo orden">
      <label for="buscar-orden">Ordenar por</label>
      <select id="buscar-orden" bind:value={orden} disabled={!hayCriterio}>
        {#each Object.entries(ORDENES) as [valor, o]}<option value={valor}>{o.texto}</option>{/each}
      </select>
    </div>
    <span class="espaciador"></span>
    <button type="button" class="boton secundario" onclick={limpiar} disabled={Object.values(filtros).every((v) => !String(v).trim())}>Limpiar</button>
  </div>
</form>

{#if error}<div class="alerta error" role="alert">{error}</div>{/if}

<p class="cuenta" aria-live="polite">
  {#if criterio.error}Corrija el filtro marcado para buscar.
  {:else if buscando && !resultados.length}Buscando…
  {:else if !hayCriterio}Últimas actas registradas. Escriba un nombre o elija un filtro para buscar.
  {:else if total === 0}No se encontraron actas con esos datos.
  {:else}{total} {total === 1 ? "acta encontrada" : "actas encontradas"}{/if}
</p>

{#if resultados.length}
  <ul class="resultados">
    {#each resultados as a (a.id)}
      <li>
        <a class="resultado" href="#/pagina/{a.pagina}/{a.posicion}">
          <div class="principal">
            <div class="nombre">
              {@render marcado([a.nombres, a.apellidos].filter(Boolean).join(" "), resaltadas.nombre)}
              {#if a.estado === "borrador"}<span class="etiqueta borrador">Sin revisar</span>{/if}
            </div>
            <div class="datos">
              <span><span class="suave">Bautizo:</span> {formatearFecha(a.fecha_bautizo) || "—"}</span>
              {#if a.fecha_nacimiento}<span><span class="suave">Nacimiento:</span> {formatearFecha(a.fecha_nacimiento)}</span>{/if}
            </div>
            {#if a.padre || a.madre}
              <div class="linea"><span class="suave">Padres:</span> <span>{@render marcado(personas(a.padre, a.madre), resaltadas.padres)}</span></div>
            {/if}
            {#if a.padrino || a.madrina}
              <div class="linea"><span class="suave">Padrinos:</span> <span>{@render marcado(personas(a.padrino, a.madrina), resaltadas.padrinos)}</span></div>
            {/if}
          </div>
          <div class="ubicacion">
            <strong>{a.expand?.libro?.numero ?? "Libro"}</strong>
            <span>Folio {a.expand?.pagina?.folio ?? "—"}</span>
            {#if a.numero_acta}<span>Acta N.º {a.numero_acta}</span>{/if}
          </div>
        </a>
      </li>
    {/each}
  </ul>
  {#if hayCriterio && resultados.length < total}
    <button class="boton secundario mas" onclick={() => buscar(true)} disabled={buscando}>
      {buscando ? "Cargando…" : `Ver más (${total - resultados.length} restantes)`}
    </button>
  {/if}
{/if}

<style>
  .filtros { padding: 1rem 1.25rem 0.25rem; margin-bottom: 1rem; }
  .rejilla { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); column-gap: 1rem; }
  .rejilla .ancho { grid-column: 1 / -1; }
  .pie-filtros { align-items: flex-end; }
  .pie-filtros .campo { margin-bottom: 0.75rem; }
  .pie-filtros .boton { margin-bottom: 0.75rem; }
  .orden { max-width: 260px; }
  .cuenta { margin: 0 0 0.75rem; color: var(--texto-suave); }

  .resultados { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem; }
  .resultado {
    display: flex; gap: 1rem; justify-content: space-between; align-items: flex-start;
    padding: 0.75rem 1rem; background: var(--superficie); border: 1px solid var(--borde);
    border-radius: var(--radio); color: inherit; text-decoration: none;
  }
  .resultado:hover, .resultado:focus-visible { border-color: var(--primario); box-shadow: var(--sombra); }
  .resultado:focus-visible { outline: 3px solid var(--primario-suave); }
  .principal { min-width: 0; }
  .nombre { font-family: var(--fuente-titulo); font-size: 1.15rem; font-weight: 600; display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
  .datos { font-size: 0.95rem; display: flex; flex-wrap: wrap; gap: 0 1.25rem; }
  .linea { font-size: 0.95rem; }
  .ubicacion { display: flex; flex-direction: column; align-items: flex-end; text-align: right; font-size: 0.95rem; white-space: nowrap; }
  mark { background: #fbe7a1; color: inherit; border-radius: 2px; }
  .etiqueta { font-family: var(--fuente); font-size: 0.75rem; font-weight: 600; padding: 0.1rem 0.5rem; border-radius: 999px; }
  .etiqueta.borrador { background: #fdf1dc; color: #7a4a0c; }
  .mas { margin: 1rem auto; display: flex; }

  @media (max-width: 600px) {
    .resultado { flex-direction: column; gap: 0.4rem; }
    .ubicacion { flex-direction: row; gap: 0.75rem; align-items: baseline; white-space: normal; }
  }
</style>
