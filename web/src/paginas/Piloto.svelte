<script>
  import { onMount } from "svelte"
  import { pb, mensajeError } from "../lib/pb.js"
  import { tamano } from "../lib/respaldos.js"
  import { precisionIA, porcentaje, duracion, porHora, CAMPOS_IA } from "../lib/informe.js"

  const TIPOS = { problema: "Problema", duda: "Duda", sugerencia: "Sugerencia" }

  let informe = $state(null)
  let ia = $state(null)
  let observaciones = $state([])
  let verResueltas = $state(false)
  let respuestas = $state({})
  let error = $state("")
  let aviso = $state("")
  let exportando = $state("")

  let totales = $derived(informe ? informe.libros.reduce((t, l) => ({
    actas: t.actas + l.actas, borradores: t.borradores + l.borradores, paginas: t.paginas + l.paginas,
  }), { actas: 0, borradores: 0, paginas: 0 }) : null)
  let maximoDia = $derived(Math.max(1, ...(informe?.por_dia ?? []).map((d) => Math.max(d.actas, d.paginas))))

  async function cargarObservaciones() {
    observaciones = await pb.collection("observaciones").getFullList({
      filter: verResueltas ? "" : 'estado = "pendiente"',
      sort: "-created",
      expand: "usuario",
    })
  }

  async function cargar() {
    error = ""
    try {
      const [inf, actasIA] = await Promise.all([
        pb.send("/api/piloto/informe", { method: "GET" }),
        pb.collection("actas_bautismo").getFullList({
          filter: 'origen_datos = "ia" && estado != "borrador"',
          fields: ["estado", "ia_respuesta", ...CAMPOS_IA].join(","),
        }),
        cargarObservaciones(),
      ])
      informe = inf
      ia = precisionIA(actasIA)
    } catch (err) {
      error = mensajeError(err)
    }
  }

  async function responder(o, estado) {
    aviso = ""
    error = ""
    try {
      await pb.collection("observaciones").update(o.id, { estado, respuesta: respuestas[o.id] ?? o.respuesta })
      await cargarObservaciones()
      aviso = estado === "resuelta" ? "Observación marcada como resuelta." : "Observación reabierta."
    } catch (err) {
      error = mensajeError(err)
    }
  }

  async function exportar(libro) {
    error = ""
    exportando = libro.id
    try {
      const r = await fetch(`/api/exportar/actas/${libro.id}`, { headers: { Authorization: pb.authStore.token } })
      if (!r.ok) throw new Error((await r.json().catch(() => ({}))).message || "No se pudo exportar.")
      const url = URL.createObjectURL(await r.blob())
      const a = Object.assign(document.createElement("a"), { href: url, download: `actas ${libro.numero}.csv` })
      document.body.append(a)
      a.click()
      a.remove()
      setTimeout(() => URL.revokeObjectURL(url), 10000)
    } catch (err) {
      error = mensajeError(err)
    } finally {
      exportando = ""
    }
  }

  const fecha = (iso) => new Date(String(iso).replace(" ", "T")).toLocaleString("es", { dateStyle: "short", timeStyle: "short" })
  const dia = (iso) => new Date(`${iso}T12:00:00`).toLocaleDateString("es", { weekday: "short", day: "numeric", month: "short" })

  onMount(cargar)
</script>

<h1>Informe del piloto</h1>
<p class="suave">Avance, ritmo de trabajo y acierto de la IA, para evaluar el sistema con un libro real.</p>

{#if aviso}<div class="alerta exito" role="status">{aviso}</div>{/if}
{#if error}<div class="alerta error" role="alert">{error}</div>{/if}

{#if informe && totales}
  <div class="cifras">
    <div class="tarjeta"><span class="numero">{totales.paginas}</span><span>Páginas</span></div>
    <div class="tarjeta"><span class="numero">{totales.actas}</span><span>Actas ({totales.borradores} sin revisar)</span></div>
    <div class="tarjeta"><span class="numero">{informe.certificados}</span><span>Certificados emitidos</span></div>
    <div class="tarjeta"><span class="numero">{informe.observaciones_pendientes}</span><span>Observaciones pendientes</span></div>
    <div class="tarjeta"><span class="numero">{tamano(informe.espacio.base + informe.espacio.imagenes)}</span><span>Espacio usado</span></div>
  </div>

  <section>
    <h2>Avance por libro</h2>
    <div class="tabla-contenedor">
      <table class="tabla">
        <thead>
          <tr><th>Libro</th><th>Páginas</th><th>Sin separar</th><th>Completas</th><th>Actas</th><th>Sin revisar</th><th>Leídas con IA</th><th>Certificados</th><th></th></tr>
        </thead>
        <tbody>
          {#each informe.libros as l (l.id)}
            <tr>
              <td><strong>{l.numero}</strong></td>
              <td>{l.paginas}</td><td>{l.sin_separar}</td><td>{l.completas}</td>
              <td>{l.actas}</td><td>{l.borradores}</td><td>{l.con_ia}</td><td>{l.certificados}</td>
              <td>
                <button class="boton secundario chico" onclick={() => exportar(l)} disabled={!l.actas || !!exportando}>
                  {exportando === l.id ? "Exportando…" : "Excel"}
                </button>
              </td>
            </tr>
          {:else}
            <tr><td colspan="9" class="suave">Todavía no hay libros.</td></tr>
          {/each}
        </tbody>
      </table>
    </div>
    <p class="ayuda suave">«Excel» descarga todas las actas del libro en un archivo que abre Excel o LibreOffice.</p>
  </section>

  <section>
    <h2>Ritmo de trabajo <span class="suave">(últimos {informe.dias} días)</span></h2>
    {#if informe.personas.length}
      <div class="tabla-contenedor">
        <table class="tabla">
          <thead><tr><th>Persona</th><th>Actas cargadas</th><th>Actas revisadas</th><th>Tiempo de trabajo</th><th>Actas por hora</th></tr></thead>
          <tbody>
            {#each informe.personas as p}
              <tr><td>{p.nombre}</td><td>{p.actas}</td><td>{p.revisadas}</td><td>{duracion(p.minutos)}</td><td>{porHora(p.actas, p.minutos)}</td></tr>
            {/each}
          </tbody>
        </table>
      </div>
      <p class="ayuda suave">El tiempo se estima por cuándo se guardó cada acta: una pausa de más de 20 minutos cuenta como otra sesión.</p>
    {:else}
      <p class="suave">Todavía no se cargaron actas.</p>
    {/if}
  </section>

  {#if informe.por_dia.length}
    <section>
      <h2>Actividad por día</h2>
      <ul class="dias">
        {#each informe.por_dia.slice(0, 14) as d}
          <li>
            <span class="fecha">{dia(d.dia)}</span>
            <span class="barras">
              <span class="barra paginas" style="width: {(100 * d.paginas) / maximoDia}%"></span>
              <span class="barra actas" style="width: {(100 * d.actas) / maximoDia}%"></span>
            </span>
            <span class="valores">{d.paginas} {d.paginas === 1 ? "página" : "páginas"} · {d.actas} {d.actas === 1 ? "acta" : "actas"}</span>
          </li>
        {/each}
      </ul>
      <p class="leyenda suave"><span class="muestra paginas"></span> páginas fotografiadas <span class="muestra actas"></span> actas cargadas</p>
    </section>
  {/if}

  <section>
    <h2>Acierto de la IA</h2>
    {#if ia && ia.actas}
      <p>
        En <strong>{ia.actas}</strong> actas revisadas, la IA propuso <strong>{ia.total.propuestos}</strong> datos y
        <strong>{porcentaje(ia.total.aceptados, ia.total.propuestos)}</strong> quedaron sin cambios.
        Los marcados en amarillo acertaron el {porcentaje(ia.porMarca.ia.aceptados, ia.porMarca.ia.propuestos)};
        los marcados en rojo (dudosos), el {porcentaje(ia.porMarca.dudoso.aceptados, ia.porMarca.dudoso.propuestos)}.
      </p>
      <div class="tabla-contenedor">
        <table class="tabla">
          <thead><tr><th>Dato</th><th>Propuestos</th><th>Sin cambios</th><th>Corregidos</th><th>Completados a mano</th></tr></thead>
          <tbody>
            {#each ia.campos.filter((c) => c.propuestos || c.agregados) as c}
              <tr>
                <td>{c.etiqueta}</td><td>{c.propuestos}</td>
                <td>{c.aceptados} <span class="suave">({porcentaje(c.aceptados, c.propuestos)})</span></td>
                <td>{c.corregidos}</td><td>{c.agregados}</td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    {:else}
      <p class="suave">Todavía no hay actas leídas por la IA y revisadas.</p>
    {/if}
  </section>

  <section>
    <div class="fila titulo">
      <h2>Observaciones</h2>
      <label class="casilla"><input type="checkbox" bind:checked={verResueltas} onchange={cargarObservaciones} /> Ver también las resueltas</label>
    </div>
    {#if observaciones.length}
      <ul class="observaciones">
        {#each observaciones as o (o.id)}
          <li class="tarjeta" class:resuelta={o.estado === "resuelta"}>
            <div class="cabecera">
              <span class="etiqueta {o.tipo}">{TIPOS[o.tipo]}</span>
              <span class="suave">{fecha(o.created)} · {o.expand?.usuario?.name || o.expand?.usuario?.email || "—"}{o.pantalla ? ` · pantalla «${o.pantalla}»` : ""}</span>
            </div>
            <p class="texto">{o.texto}</p>
            <div class="fila">
              <div class="campo">
                <label for="resp-{o.id}">Respuesta</label>
                <input id="resp-{o.id}" value={respuestas[o.id] ?? o.respuesta} oninput={(e) => (respuestas[o.id] = e.currentTarget.value)} maxlength="3000" />
              </div>
              {#if o.estado === "pendiente"}
                <button class="boton" onclick={() => responder(o, "resuelta")}>Marcar resuelta</button>
              {:else}
                <button class="boton secundario" onclick={() => responder(o, "pendiente")}>Reabrir</button>
              {/if}
            </div>
          </li>
        {/each}
      </ul>
    {:else}
      <p class="suave">{verResueltas ? "No hay observaciones." : "No hay observaciones pendientes."}</p>
    {/if}
    <p class="ayuda suave">Cada persona las anota con el botón «Anotar un problema» del menú.</p>
  </section>
{/if}

<style>
  section { margin-bottom: 2rem; }
  h2 .suave { font-size: 1rem; font-weight: 400; }
  .cifras { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin: 1.5rem 0; }
  .cifras .tarjeta { display: flex; flex-direction: column; gap: 0.25rem; padding: 1rem 1.25rem; }
  .numero { font-family: var(--fuente-titulo); font-size: 1.7rem; font-weight: 600; color: var(--primario); }
  .ayuda { font-size: 0.88rem; margin: 0.5rem 0 0; }
  .chico { min-height: 34px; padding: 0.25rem 0.75rem; font-size: 0.9rem; }
  .dias { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.4rem; }
  .dias li { display: grid; grid-template-columns: 7.5rem 1fr auto; gap: 0.75rem; align-items: center; font-size: 0.95rem; }
  .barras { display: flex; flex-direction: column; gap: 2px; }
  .barra { display: block; height: 8px; border-radius: 4px; min-width: 2px; }
  .barra.paginas, .muestra.paginas { background: #c9a227; }
  .barra.actas, .muestra.actas { background: var(--primario); }
  .valores { color: var(--texto-suave); white-space: nowrap; }
  .leyenda { font-size: 0.88rem; display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }
  .muestra { display: inline-block; width: 14px; height: 8px; border-radius: 4px; margin-left: 0.5rem; }
  .titulo { justify-content: space-between; margin-bottom: 0.75rem; }
  .titulo h2 { margin: 0; }
  .casilla { display: flex; gap: 0.4rem; align-items: center; }
  .observaciones { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem; }
  .observaciones .tarjeta { padding: 1rem 1.25rem; }
  .resuelta { opacity: 0.75; }
  .cabecera { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; font-size: 0.9rem; }
  .texto { white-space: pre-wrap; margin: 0.5rem 0 0.75rem; }
  .observaciones .fila { align-items: flex-end; }
  .observaciones .campo { flex: 1 1 260px; margin-bottom: 0; }
  .etiqueta { font-size: 0.8rem; font-weight: 600; padding: 0.1rem 0.5rem; border-radius: 999px; }
  .etiqueta.problema { background: var(--error-suave); color: var(--error); }
  .etiqueta.duda { background: #e7eefb; color: #24467a; }
  .etiqueta.sugerencia { background: #e8f3ea; color: var(--exito); }
  @media (max-width: 600px) {
    .dias li { grid-template-columns: 1fr; gap: 0.2rem; }
  }
</style>
