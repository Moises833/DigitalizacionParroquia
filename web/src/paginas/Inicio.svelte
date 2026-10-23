<script>
  import { onMount } from "svelte"
  import { pb } from "../lib/pb.js"
  import { sesion, esAdmin } from "../lib/sesion.svelte.js"
  import { avisoRespaldo } from "../lib/respaldos.js"

  let parroquia = $state("")
  let totales = $state(null)
  let respaldo = $state("")

  async function contar(coleccion, filtro = "") {
    const r = await pb.collection(coleccion).getList(1, 1, { filter: filtro, skipTotal: false, fields: "id" })
    return r.totalItems
  }

  onMount(async () => {
    pb.send("/api/respaldos/estado", { method: "GET" })
      .then((e) => (respaldo = avisoRespaldo(e)))
      .catch(() => { /* el aviso no es imprescindible */ })
    const [p, libros, paginas, actas, borradores] = await Promise.all([
      pb.collection("parroquia").getList(1, 1),
      contar("libros"),
      contar("paginas"),
      contar("actas_bautismo"),
      contar("actas_bautismo", 'estado = "borrador"'),
    ])
    parroquia = p.items[0]?.nombre ?? ""
    totales = { libros, paginas, actas, borradores }
  })
</script>

<h1>{parroquia || "Inicio"}</h1>
<p class="suave">Hola, {sesion.usuario.name || sesion.usuario.email}.</p>

{#if respaldo}
  <div class="alerta error" role="status">
    <strong>Respaldos:</strong> {respaldo}
    {#if esAdmin()}<a href="#/respaldos">Revisar los respaldos</a>{:else}Avise al administrador del sistema.{/if}
  </div>
{/if}

{#if totales}
  <div class="resumen">
    <div class="tarjeta"><span class="numero">{totales.libros}</span><span>Libros</span></div>
    <div class="tarjeta"><span class="numero">{totales.paginas}</span><span>Páginas digitalizadas</span></div>
    <div class="tarjeta"><span class="numero">{totales.actas}</span><span>Actas registradas</span></div>
    <div class="tarjeta"><span class="numero">{totales.borradores}</span><span>Actas por revisar</span></div>
  </div>
{/if}

{#if totales && totales.libros === 0}
  <div class="tarjeta">
    <h2>Primer paso</h2>
    <p>Registre los libros de bautismo de la parroquia para empezar a digitalizarlos.</p>
    <a class="boton" href="#/libros">Ir a Libros</a>
  </div>
{/if}

<style>
  .resumen { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin: 1.5rem 0; }
  .resumen .tarjeta { display: flex; flex-direction: column; gap: 0.25rem; padding: 1.25rem; }
  .numero { font-family: var(--fuente-titulo); font-size: 2rem; font-weight: 600; color: var(--primario); }
  a.boton { text-decoration: none; }
  .alerta a { color: inherit; font-weight: 600; margin-left: 0.25rem; }
</style>
