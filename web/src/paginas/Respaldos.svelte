<script>
  import { onMount } from "svelte"
  import { pb, mensajeError } from "../lib/pb.js"
  import { hace, tamano } from "../lib/respaldos.js"

  const DESTINOS = { carpeta: "Carpeta", nube: "Nube" }
  const ESTADOS = { ok: "Completo", parcial: "En curso", error: "Error" }

  let config = $state(null)
  let estado = $state(null)
  let historial = $state([])
  let copias = $state({ carpeta: null, nube: null })
  let secreto = $state("")
  let aviso = $state("")
  let error = $state("")
  let ocupado = $state("")

  async function cargar() {
    const [lista, e, h] = await Promise.all([
      pb.collection("config_respaldo").getFullList(),
      pb.send("/api/respaldos/estado", { method: "GET" }),
      pb.collection("respaldos").getList(1, 20, { sort: "-created" }),
    ])
    config = lista[0]
    estado = e
    historial = h.items
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

  const fecha = (iso) => (iso ? new Date(iso).toLocaleString("es", { dateStyle: "short", timeStyle: "short" }) : "")

  const guardar = (campos, mensaje) => (evento) => {
    evento?.preventDefault()
    accion("Guardando…", async () => {
      const datos = Object.fromEntries(campos.map((c) => [c, typeof config[c] === "string" ? config[c].trim() : config[c]]))
      await pb.collection("config_respaldo").update(config.id, datos)
      aviso = mensaje
    })
  }

  const guardarCarpeta = guardar(["carpeta_activa", "carpeta_ruta"], "Carpeta guardada.")
  const guardarNube = guardar(["nube_activa", "s3_endpoint", "s3_region", "s3_bucket", "s3_prefijo", "s3_clave_id"], "Datos de la nube guardados.")
  const guardarFrecuencia = guardar(["cada_horas", "conservar"], "Frecuencia guardada.")

  const guardarSecreto = () => accion("Guardando clave…", async () => {
    await pb.send("/api/respaldos/secreto", { method: "POST", body: { secreto } })
    secreto = ""
    aviso = "Clave secreta guardada. Use «Probar» para comprobarla."
  })

  const probar = (destino) => accion("Probando…", async () => {
    const r = await pb.send(`/api/respaldos/probar/${destino}`, { method: "POST" })
    aviso = `Funciona: se pudo escribir y leer en ${r.destino}.`
  })

  const verCopias = (destino) => accion("Buscando copias…", async () => {
    copias[destino] = await pb.send(`/api/respaldos/copias/${destino}`, { method: "GET" })
  })

  const respaldarAhora = () => accion("Respaldando (hasta un minuto)…", async () => {
    const r = await pb.send("/api/respaldos/ejecutar", { method: "POST" })
    if (r.ocupado) {
      aviso = "Ya hay un respaldo en curso. Espere un momento y actualice."
      return
    }
    if (!r.resultados.length) {
      aviso = "No hay ningún destino activo."
      return
    }
    const partes = r.resultados.map((x) => {
      const nombre = DESTINOS[x.destino]
      if (x.estado === "error") return `${nombre}: error.`
      if (x.estado === "parcial") return `${nombre}: copiados ${x.copiados} archivos; sigue solo en segundo plano.`
      return `${nombre}: listo (${x.copiados} archivos nuevos, ${tamano(x.bytes)}).`
    })
    if (r.resultados.some((x) => x.estado === "error")) error = partes.join(" ")
    else aviso = partes.join(" ")
  })

  onMount(() => accion("Cargando…", async () => {}))
</script>

{#snippet estadoDestino(d)}
  {@const e = estado[d]}
  <div class="destino">
    <h3>{DESTINOS[d]}</h3>
    {#if !e.activa}
      <p class="suave">Desactivado.</p>
    {:else}
      <p>
        {#if e.ultimo_ok}Último respaldo completo: <strong>{hace(e.ultimo_ok)}</strong> <span class="suave">({fecha(e.ultimo_ok)})</span>
        {:else}<strong>Todavía no se completó ningún respaldo.</strong>{/if}
      </p>
      {#if e.pendiente}<p class="en-curso">Copiando: el respaldo avanza solo, un poco cada minuto.</p>{/if}
      {#if e.error}<p class="alerta error">{e.error}</p>{/if}
    {/if}
  </div>
{/snippet}

{#snippet listaCopias(d)}
  {#if copias[d]}
    {#if copias[d].length}
      <ul class="copias">
        {#each copias[d] as c}<li>{c.nombre.replace(/^db\//, "")} <span class="suave">({tamano(c.tam)})</span></li>{/each}
      </ul>
    {:else}
      <p class="suave">Todavía no hay copias en este destino.</p>
    {/if}
  {/if}
{/snippet}

<h1>Respaldos</h1>
<p class="suave">
  Copia de la base de datos y de todas las imágenes. Se hace sola mientras la computadora está encendida;
  cada vez solo se copia lo nuevo, así que después del primero es rápido.
</p>

{#if aviso}<div class="alerta exito" role="status">{aviso}</div>{/if}
{#if error}<div class="alerta error" role="alert">{error}</div>{/if}
{#if ocupado}<p class="suave" aria-live="polite">{ocupado}</p>{/if}

{#if config && estado}
  <section class="tarjeta">
    <h2>Estado</h2>
    <div class="estados">
      {@render estadoDestino("carpeta")}
      {@render estadoDestino("nube")}
    </div>
    <button class="boton" onclick={respaldarAhora} disabled={!!ocupado}>Respaldar ahora</button>
    <p class="ayuda suave">Conviene pulsarlo al terminar una jornada de mucho trabajo.</p>
  </section>

  <section class="tarjeta">
    <h2>Carpeta</h2>
    <form onsubmit={guardarCarpeta}>
      <label class="interruptor"><input type="checkbox" bind:checked={config.carpeta_activa} /> Respaldar en una carpeta</label>
      <div class="campo">
        <label for="carpeta">Carpeta de destino</label>
        <input id="carpeta" bind:value={config.carpeta_ruta} placeholder="Por ejemplo E:\Respaldos Bautismos" maxlength="500" spellcheck="false" />
        <span class="ayuda">
          Si queda vacío se usa <code>{estado.carpeta.ruta}</code>. Mejor en <strong>otro disco o un disco externo</strong>
          que quede conectado: si se daña el disco de la computadora, se pierden los datos y su copia juntos.
        </span>
      </div>
      <div class="fila">
        <button class="boton" type="submit" disabled={!!ocupado}>Guardar</button>
        <button class="boton secundario" type="button" onclick={() => probar("carpeta")} disabled={!!ocupado}>Probar</button>
        <button class="boton secundario" type="button" onclick={() => verCopias("carpeta")} disabled={!!ocupado}>Ver copias</button>
      </div>
    </form>
    {@render listaCopias("carpeta")}
  </section>

  <section class="tarjeta">
    <h2>Nube</h2>
    <p class="suave">
      Sirve cualquier servicio compatible con S3. Por ejemplo, <strong>Backblaze B2</strong> tiene 10 GB gratis:
      cree una cuenta con el correo de la parroquia, un <em>bucket</em> privado y una <em>Application Key</em>
      con acceso solo a ese bucket, y copie aquí sus datos.
    </p>
    <form onsubmit={guardarNube}>
      <label class="interruptor"><input type="checkbox" bind:checked={config.nube_activa} /> Respaldar en la nube</label>
      <div class="rejilla">
        <div class="campo">
          <label for="s3-endpoint">Endpoint</label>
          <input id="s3-endpoint" bind:value={config.s3_endpoint} placeholder="https://s3.us-west-004.backblazeb2.com" spellcheck="false" />
        </div>
        <div class="campo">
          <label for="s3-region">Región</label>
          <input id="s3-region" bind:value={config.s3_region} placeholder="us-west-004" spellcheck="false" />
        </div>
        <div class="campo">
          <label for="s3-bucket">Bucket</label>
          <input id="s3-bucket" bind:value={config.s3_bucket} spellcheck="false" />
        </div>
        <div class="campo">
          <label for="s3-prefijo">Prefijo (carpeta dentro del bucket)</label>
          <input id="s3-prefijo" bind:value={config.s3_prefijo} spellcheck="false" />
        </div>
        <div class="campo">
          <label for="s3-clave">Key ID</label>
          <input id="s3-clave" bind:value={config.s3_clave_id} autocomplete="off" spellcheck="false" />
        </div>
      </div>
      <div class="fila">
        <button class="boton" type="submit" disabled={!!ocupado}>Guardar</button>
        <button class="boton secundario" type="button" onclick={() => probar("nube")} disabled={!!ocupado || !config.secreto_fin}>Probar</button>
        <button class="boton secundario" type="button" onclick={() => verCopias("nube")} disabled={!!ocupado || !config.secreto_fin}>Ver copias</button>
      </div>
    </form>
    {@render listaCopias("nube")}

    <form class="fila secreto" onsubmit={(e) => { e.preventDefault(); guardarSecreto() }}>
      <div class="campo">
        <label for="s3-secreto">Clave secreta (Application Key)</label>
        <input id="s3-secreto" type="password" bind:value={secreto} autocomplete="off" maxlength="300" />
        <span class="ayuda">
          {#if config.secreto_fin}Hay una guardada (termina en …{config.secreto_fin}).{:else}No hay ninguna guardada.{/if}
          Queda solo en el servidor y nunca se muestra.
        </span>
      </div>
      <button class="boton" type="submit" disabled={!secreto.trim() || !!ocupado}>Guardar clave</button>
    </form>

    <div class="alerta aviso-privacidad">
      <strong>Privacidad:</strong> la copia tiene los datos de todas las actas y las fotos de los libros. Use un bucket
      <strong>privado</strong>, una clave con acceso solo a ese bucket, y guarde los datos de acceso en un lugar seguro
      fuera de la computadora: sin ellos no se puede recuperar la copia.
    </div>
  </section>

  <section class="tarjeta">
    <h2>Frecuencia</h2>
    <form class="fila" onsubmit={guardarFrecuencia}>
      <div class="campo">
        <label for="cada-horas">Respaldar cada (horas)</label>
        <input id="cada-horas" type="number" min="1" max="168" bind:value={config.cada_horas} />
        <span class="ayuda">Si la computadora estaba apagada, se hace apenas se enciende.</span>
      </div>
      <div class="campo">
        <label for="conservar">Copias de la base que se guardan</label>
        <input id="conservar" type="number" min="1" max="365" bind:value={config.conservar} />
        <span class="ayuda">Las más viejas se borran. Las imágenes no se borran nunca del respaldo.</span>
      </div>
      <button class="boton" type="submit" disabled={!!ocupado}>Guardar</button>
    </form>
  </section>

  {#if historial.length}
    <section>
      <h2>Últimos respaldos</h2>
      <div class="tabla-contenedor">
        <table class="tabla">
          <thead><tr><th>Fecha</th><th>Destino</th><th>Estado</th><th>Archivos nuevos</th><th>Detalle</th></tr></thead>
          <tbody>
            {#each historial as h (h.id)}
              <tr>
                <td class="nowrap">{fecha(h.created)}</td>
                <td>{DESTINOS[h.destino]}{h.manual ? " (manual)" : ""}</td>
                <td><span class="etiqueta {h.estado}">{ESTADOS[h.estado]}</span></td>
                <td>{h.copiados} <span class="suave">({tamano(h.bytes)})</span></td>
                <td class="detalle">{h.error || (h.base ? h.base.split("/").pop() : "")}</td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    </section>
  {/if}

  <section class="tarjeta">
    <h2>Restaurar</h2>
    <ol>
      <li>En la carpeta del sistema (<code>C:\SistemaBautismos</code>), abra <code>restaurar_respaldo.bat</code>. Cierra el sistema solo.</li>
      <li>Elija si restaura desde una carpeta (otro disco o pendrive) o desde la nube, y qué copia usar (la 1 es la más reciente).</li>
      <li>Los datos actuales no se borran: quedan en una carpeta <code>pb_data_antes_…</code>.</li>
    </ol>
    <p class="suave">Si la computadora se dañó, primero instale el sistema en la nueva y después restaure desde la nube o el disco externo.</p>
  </section>
{/if}

<style>
  section { margin-bottom: 1.25rem; }
  .estados { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 0 1.5rem; margin-bottom: 0.5rem; }
  .destino h3 { font-size: 1.05rem; margin: 0 0 0.25rem; }
  .destino p { margin: 0 0 0.5rem; }
  .en-curso { color: #7a4a0c; }
  .rejilla { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); column-gap: 1rem; }
  .fila { align-items: flex-end; }
  form.fila .campo { margin-bottom: 0; max-width: 340px; }
  .secreto { margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--borde); }
  .ayuda { font-size: 0.85rem; }
  .interruptor { display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 1rem; }
  .interruptor input { width: 20px; height: 20px; }
  .aviso-privacidad { background: #fdf6e3; border-left: 4px solid #d9b45a; margin: 1rem 0 0; font-size: 0.92rem; }
  .copias { margin: 0.75rem 0 0; padding-left: 1.25rem; font-size: 0.95rem; }
  code { font-size: 0.9em; background: var(--fondo); padding: 0 0.25rem; border-radius: 4px; overflow-wrap: anywhere; }
  .nowrap { white-space: nowrap; }
  .detalle { font-size: 0.9rem; overflow-wrap: anywhere; }
  .etiqueta { font-size: 0.8rem; font-weight: 600; padding: 0.1rem 0.5rem; border-radius: 999px; white-space: nowrap; }
  .etiqueta.ok { background: #e8f3ea; color: var(--exito); }
  .etiqueta.parcial { background: #fdf1dc; color: #7a4a0c; }
  .etiqueta.error { background: var(--error-suave); color: var(--error); }
  ol { padding-left: 1.25rem; }
</style>
