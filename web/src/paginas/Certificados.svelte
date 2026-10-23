<script>
  import { onMount } from "svelte"
  import { pb, mensajeError } from "../lib/pb.js"
  import { formatearFecha } from "../lib/fechas.js"
  import { palabras } from "../lib/busqueda.js"
  import { nombreCompleto } from "../lib/certificado.js"

  const POR_PAGINA = 30

  let texto = $state("")
  let certificados = $state([])
  let total = $state(0)
  let pagina = $state(1)
  let cargando = $state(false)
  let error = $state("")
  let temporizador = null
  let consulta = 0

  // Un número ("2026-0012", "12") o palabras del nombre del bautizado.
  function filtro() {
    const t = texto.trim()
    if (!t) return ""
    if (/^[\d-]+$/.test(t)) return pb.filter("numero ~ {:t}", { t })
    const lista = palabras(t)
    const parametros = Object.fromEntries(lista.map((palabra, i) => [`p${i}`, palabra]))
    return pb.filter(lista.map((_, i) => `acta.b_nombre ~ {:p${i}}`).join(" && "), parametros)
  }

  async function cargar(mas = false) {
    const numero = ++consulta
    cargando = true
    error = ""
    try {
      const siguiente = mas ? pagina + 1 : 1
      const r = await pb.collection("certificados").getList(siguiente, POR_PAGINA, {
        filter: filtro(),
        sort: "-anio,-correlativo",
        expand: "emitido_por",
        fields: "id,acta,numero,datos,fines,fecha_expedicion,expand.emitido_por.name,expand.emitido_por.email",
      })
      if (numero !== consulta) return
      certificados = mas ? [...certificados, ...r.items] : r.items
      total = r.totalItems
      pagina = siguiente
    } catch (err) {
      if (numero === consulta) error = mensajeError(err)
    } finally {
      if (numero === consulta) cargando = false
    }
  }

  function cambio() {
    clearTimeout(temporizador)
    temporizador = setTimeout(() => cargar(false), 250)
  }

  onMount(() => cargar(false))
</script>

<h1>Certificados emitidos</h1>
<p class="suave">Para emitir una fe de bautismo, busque el acta en <a href="#/buscar">Buscar</a> y pulse «Fe de bautismo».</p>

<div class="campo buscador">
  <label for="buscar-certificado">Buscar por número o nombre</label>
  <input id="buscar-certificado" bind:value={texto} oninput={cambio} autocomplete="off" spellcheck="false"
         placeholder="2026-0012 o Pérez Rosa" />
</div>

{#if error}<div class="alerta error" role="alert">{error}</div>{/if}

{#if !cargando && !certificados.length && !error}
  <p class="suave">{texto.trim() ? "No hay certificados con esos datos." : "Todavía no se emitió ningún certificado."}</p>
{:else if certificados.length}
  <div class="tabla-contenedor">
    <table class="tabla">
      <thead><tr><th>N.º</th><th>Bautizado</th><th>Expedido</th><th>Para fines de</th><th>Emitido por</th></tr></thead>
      <tbody>
        {#each certificados as c (c.id)}
          <tr>
            <td class="numero"><a href="#/certificado/{c.acta}/{c.id}">{c.numero}</a></td>
            <td>{nombreCompleto(c.datos)}</td>
            <td>{formatearFecha(c.fecha_expedicion)}</td>
            <td>{c.fines || "—"}</td>
            <td>{c.expand?.emitido_por?.name || c.expand?.emitido_por?.email || "—"}</td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
  {#if certificados.length < total}
    <button class="boton secundario mas" onclick={() => cargar(true)} disabled={cargando}>
      {cargando ? "Cargando…" : `Ver más (${total - certificados.length} restantes)`}
    </button>
  {/if}
{/if}

<style>
  .buscador { max-width: 420px; }
  .numero { white-space: nowrap; font-variant-numeric: tabular-nums; }
  .mas { margin: 1rem auto; display: flex; }
</style>
