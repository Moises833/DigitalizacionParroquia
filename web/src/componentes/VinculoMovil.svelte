<script>
  import { onDestroy } from "svelte"
  import { renderSVG } from "uqr"
  import { pb, mensajeError } from "../lib/pb.js"

  let { libro, folioSugerido } = $props()

  const LOCALES = ["localhost", "127.0.0.1", "[::1]"]

  let sesion = $state(null)
  let direcciones = $state([])
  let direccion = $state("")
  let folio = $state("")
  let error = $state("")
  let cargando = $state(false)
  let cancelarSuscripcion = null

  let enlace = $derived(sesion && direccion ? `http://${direccion}:${sesion.puerto}/#/movil/${sesion.token}` : "")
  let qr = $derived(enlace ? renderSVG(enlace, { ecc: "M", border: 2 }) : "")
  let sinRed = $derived(sesion && direcciones.length === 0)

  async function vincular() {
    error = ""
    cargando = true
    try {
      sesion = await pb.send("/api/captura/sesiones", { method: "POST", body: { libro: libro.id } })
      folio = String(sesion.folio_siguiente)

      // Si esta PC ya se abrió por la IP de red, esa dirección es la que seguro funciona.
      const actual = window.location.hostname
      direcciones = [
        ...(LOCALES.includes(actual) ? [] : [{ ip: actual, interfaz: "Dirección actual", virtual: false }]),
        ...sesion.direcciones.filter((d) => d.ip !== actual),
      ]
      direccion = direcciones[0]?.ip ?? ""

      cancelarSuscripcion?.()
      cancelarSuscripcion = await pb.collection("sesiones_captura").subscribe(sesion.id, (e) => {
        if (!sesion) return
        sesion.fotos_recibidas = e.record.fotos_recibidas
        sesion.folio_siguiente = e.record.folio_siguiente
        if (document.activeElement?.id !== "folio-movil") folio = String(e.record.folio_siguiente)
      })
    } catch (err) {
      error = mensajeError(err)
    } finally {
      cargando = false
    }
  }

  async function cambiarFolio() {
    const n = parseInt(folio, 10)
    if (!sesion || !(n >= 1) || n === sesion.folio_siguiente) return
    try {
      const r = await pb.send(`/api/captura/sesiones/${sesion.id}`, { method: "PATCH", body: { folio_siguiente: n } })
      sesion.folio_siguiente = r.folio_siguiente
    } catch (err) {
      error = mensajeError(err)
    }
  }

  async function terminar() {
    if (!sesion) return
    try {
      await pb.send(`/api/captura/sesiones/${sesion.id}`, { method: "PATCH", body: { activa: false } })
    } catch {
      // Si ya venció, igual se cierra en pantalla.
    }
    cancelarSuscripcion?.()
    cancelarSuscripcion = null
    sesion = null
  }

  onDestroy(() => {
    cancelarSuscripcion?.()
  })
</script>

<section class="tarjeta">
  <h2>Usar el teléfono como cámara</h2>

  {#if error}<div class="alerta error" role="alert">{error}</div>{/if}

  {#if !sesion}
    <p>El teléfono debe estar conectado a la <strong>misma red Wi-Fi</strong> que esta computadora.
      Las fotos llegarán a <strong>{libro.numero}</strong> a partir del folio {folioSugerido}.</p>
    <button class="boton" onclick={vincular} disabled={cargando}>
      {cargando ? "Preparando…" : "Mostrar código QR"}
    </button>
  {:else}
    <div class="vinculo">
      <div class="qr" aria-label="Código QR para abrir la cámara en el teléfono">
        {#if qr}{@html qr}{:else}<p class="suave">Sin dirección de red</p>{/if}
      </div>

      <div class="datos">
        <ol>
          <li>Abra la cámara del teléfono y apunte al código.</li>
          <li>Toque el enlace que aparece.</li>
          <li>Tome las fotos: llegan aquí al instante.</li>
        </ol>

        {#if sinRed}
          <div class="alerta error">No se encontró una red local. Conecte esta PC al Wi-Fi o al cable de red e intente de nuevo.</div>
        {:else if direcciones.length > 1}
          <div class="campo">
            <label for="direccion">Red</label>
            <select id="direccion" bind:value={direccion}>
              {#each direcciones as d (d.ip)}
                <option value={d.ip}>{d.ip} — {d.interfaz}{d.virtual ? " (probablemente virtual)" : ""}</option>
              {/each}
            </select>
            <span class="ayuda">Si el teléfono no abre la página, pruebe con otra red de la lista.</span>
          </div>
        {/if}

        {#if enlace}<p class="enlace suave">{enlace.replace(/#.*/, "")}</p>{/if}

        <div class="fila">
          <div class="campo">
            <label for="folio-movil">Próximo folio</label>
            <input id="folio-movil" type="number" min="1" bind:value={folio} onchange={cambiarFolio} />
          </div>
          <div class="contador">
            <span class="numero">{sesion.fotos_recibidas}</span>
            <span>fotos recibidas</span>
          </div>
        </div>

        <button class="boton secundario" onclick={terminar}>Terminar vinculación</button>
        <p class="ayuda suave">El código deja de funcionar al terminar o a las 8 horas.</p>
      </div>
    </div>
  {/if}
</section>

<style>
  section { margin-bottom: 1.5rem; }
  .vinculo { display: flex; gap: 2rem; flex-wrap: wrap; align-items: flex-start; }
  .qr { width: 260px; max-width: 100%; aspect-ratio: 1; background: #fff; border: 1px solid var(--borde); border-radius: var(--radio); padding: 0.5rem; }
  .qr :global(svg) { width: 100%; height: 100%; display: block; }
  .datos { flex: 1 1 280px; }
  ol { padding-left: 1.2rem; margin-top: 0; }
  .enlace { font-family: ui-monospace, Consolas, monospace; font-size: 0.9rem; word-break: break-all; }
  .contador { display: flex; flex-direction: column; }
  .numero { font-family: var(--fuente-titulo); font-size: 2rem; font-weight: 600; color: var(--primario); line-height: 1; }
  .fila .campo { flex: 0 1 140px; }
  .ayuda { font-size: 0.85rem; }
</style>
