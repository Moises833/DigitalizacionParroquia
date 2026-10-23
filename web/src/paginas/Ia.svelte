<script>
  import { onMount } from "svelte"
  import { pb, mensajeError } from "../lib/pb.js"
  import { estadoIA } from "../lib/ia.js"

  let config = $state(null)
  let estado = $state(null)
  let clave = $state("")
  let modelos = $state([])
  let aviso = $state("")
  let error = $state("")
  let ocupado = $state("") // texto de la acción en curso

  async function cargar() {
    const [lista, e] = await Promise.all([pb.collection("config_ia").getFullList(), estadoIA()])
    config = lista[0]
    estado = e
  }

  async function accion(texto, fn) {
    aviso = ""
    error = ""
    ocupado = texto
    try {
      await fn()
      await cargar()
    } catch (err) {
      error = mensajeError(err)
    } finally {
      ocupado = ""
    }
  }

  const guardarClave = () => accion("Guardando clave…", async () => {
    await pb.send("/api/ia/clave", { method: "POST", body: { api_key: clave } })
    clave = ""
    aviso = "Clave guardada. Use «Probar conexión» para comprobarla."
  })

  const quitarClave = () => {
    if (!confirm("¿Quitar la clave de Gemini? La IA dejará de funcionar hasta cargar otra.")) return
    accion("Quitando clave…", async () => {
      await pb.send("/api/ia/clave", { method: "POST", body: { api_key: "" } })
      aviso = "Clave quitada."
    })
  }

  const probar = () => accion("Probando conexión…", async () => {
    const r = await pb.send("/api/ia/probar", { method: "POST" })
    modelos = r.modelos
    aviso = r.modelos.length
      ? `Conexión correcta: la clave puede usar ${r.modelos.length} modelos Gemini.`
      : "La clave funciona, pero no tiene modelos Gemini disponibles."
  })

  const guardarAjustes = (evento) => {
    evento.preventDefault()
    accion("Guardando…", async () => {
      await pb.collection("config_ia").update(config.id, {
        modelo: config.modelo.trim(),
        activo: config.activo,
        por_minuto: config.por_minuto,
        por_dia: config.por_dia,
      })
      aviso = "Ajustes guardados."
    })
  }

  const procesar = () => accion("Procesando la cola (puede tardar hasta un minuto)…", async () => {
    const r = await pb.send("/api/ia/procesar", { method: "POST" })
    aviso = `Actas leídas: ${r.procesados}. Con error: ${r.errores}.${r.motivo ? ` ${r.motivo}` : ""}`
  })

  const reintentar = () => accion("Reintentando…", async () => {
    const r = await pb.send("/api/ia/reintentar", { method: "POST" })
    aviso = `${r.reintentados} actas vuelven a la cola.`
  })

  onMount(() => accion("Cargando…", async () => {}))
</script>

<h1>Inteligencia artificial</h1>
<p class="suave">
  La IA lee cada acta recortada y propone los datos; una persona siempre los revisa antes de guardarlos.
  Las actas se envían a Google Gemini por internet; el resto del sistema funciona sin conexión.
</p>

{#if aviso}<div class="alerta exito" role="status">{aviso}</div>{/if}
{#if error}<div class="alerta error" role="alert">{error}</div>{/if}
{#if ocupado}<p class="suave" aria-live="polite">{ocupado}</p>{/if}

{#if config && estado}
  <section class="tarjeta">
    <h2>Estado</h2>
    <ul class="cifras">
      <li><strong>{estado.cola.pendiente + estado.cola.procesando}</strong> en cola</li>
      <li><strong>{estado.cola.por_revisar}</strong> leídas, por revisar</li>
      <li><strong>{estado.cola.error}</strong> con error</li>
      <li><strong>{estado.uso_dia}</strong> de {estado.por_dia} consultas hoy</li>
    </ul>
    {#if !estado.configurada}
      <p class="alerta error">Falta la clave de Gemini.</p>
    {:else if !estado.activo}
      <p class="alerta error">La IA está desactivada: las actas quedan en cola sin leerse.</p>
    {/if}
    {#if estado.ultimo_error}<p class="alerta error">Último problema: {estado.ultimo_error}</p>{/if}
    <div class="fila">
      <button class="boton" onclick={procesar} disabled={!!ocupado || !estado.activo || !estado.configurada}>Procesar ahora</button>
      <button class="boton secundario" onclick={reintentar} disabled={!!ocupado || !estado.cola.error}>Reintentar las que fallaron</button>
    </div>
    <p class="ayuda suave">La cola se procesa sola cada minuto mientras el sistema esté encendido y haya internet.</p>
  </section>

  <section class="tarjeta">
    <h2>Clave de Gemini</h2>
    <p class="suave">
      {#if config.clave_fin}Hay una clave guardada (termina en <strong>…{config.clave_fin}</strong>).{:else}No hay clave guardada.{/if}
      Se obtiene gratis en Google AI Studio (aistudio.google.com) con una cuenta de Google de la parroquia.
    </p>
    <form class="fila" onsubmit={(e) => { e.preventDefault(); guardarClave() }}>
      <div class="campo">
        <label for="clave">Nueva clave</label>
        <input id="clave" type="password" bind:value={clave} autocomplete="off" maxlength="300" />
      </div>
      <button class="boton" type="submit" disabled={!clave.trim() || !!ocupado}>Guardar clave</button>
      <button class="boton secundario" type="button" onclick={probar} disabled={!config.clave_fin || !!ocupado}>Probar conexión</button>
      {#if config.clave_fin}
        <button class="boton peligro" type="button" onclick={quitarClave} disabled={!!ocupado}>Quitar</button>
      {/if}
    </form>
    <div class="alerta aviso-privacidad">
      <strong>Privacidad:</strong> en el plan gratuito, Google puede usar lo que se le envía para mejorar sus
      productos, y personas de Google pueden revisarlo. Las actas contienen datos personales (nombres de niños,
      padres y padrinos). Antes de enviar libros completos, la parroquia debería aprobarlo o activar la
      facturación de Google Cloud, con la que Google no usa los datos.
    </div>
  </section>

  <section class="tarjeta">
    <h2>Ajustes</h2>
    <form onsubmit={guardarAjustes}>
      <label class="interruptor">
        <input type="checkbox" bind:checked={config.activo} /> IA activada
      </label>
      <div class="fila">
        <div class="campo">
          <label for="modelo">Modelo</label>
          <input id="modelo" bind:value={config.modelo} list="modelos" maxlength="100" />
          <datalist id="modelos">{#each modelos as m}<option value={m}></option>{/each}</datalist>
          <span class="ayuda">«Probar conexión» muestra los disponibles. Recomendado: un modelo «flash».</span>
        </div>
        <div class="campo">
          <label for="por-minuto">Consultas por minuto</label>
          <input id="por-minuto" type="number" min="1" max="60" bind:value={config.por_minuto} />
          <span class="ayuda">El plan gratuito permite pocas; 8 es prudente.</span>
        </div>
        <div class="campo">
          <label for="por-dia">Máximo por día</label>
          <input id="por-dia" type="number" min="1" max="100000" bind:value={config.por_dia} />
        </div>
      </div>
      <button class="boton" type="submit" disabled={!!ocupado}>Guardar ajustes</button>
    </form>
  </section>
{/if}

<style>
  section { margin-bottom: 1.25rem; }
  .cifras { list-style: none; padding: 0; margin: 0 0 1rem; display: flex; flex-wrap: wrap; gap: 0.5rem 1.5rem; }
  .cifras strong { font-size: 1.3rem; color: var(--primario); }
  .fila { align-items: flex-end; }
  .fila .campo { max-width: 320px; }
  form.fila .campo { margin-bottom: 0; }
  .ayuda { font-size: 0.85rem; }
  .interruptor { display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 1rem; }
  .interruptor input { width: 20px; height: 20px; }
  .aviso-privacidad { background: #fdf6e3; border-left: 4px solid #d9b45a; margin: 1rem 0 0; font-size: 0.92rem; }
</style>
