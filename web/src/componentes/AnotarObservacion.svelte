<script>
  import { pb, mensajeError } from "../lib/pb.js"
  import { ruta } from "../lib/ruta.svelte.js"
  import { sesion } from "../lib/sesion.svelte.js"

  const TIPOS = { problema: "Algo no funciona", duda: "Tengo una duda", sugerencia: "Una sugerencia" }

  let dialogo = $state(null)
  let tipo = $state("problema")
  let texto = $state("")
  let pantalla = ""
  let mias = $state([])
  let guardando = $state(false)
  let aviso = $state("")
  let error = $state("")

  async function abrir() {
    pantalla = ruta.actual
    aviso = ""
    error = ""
    dialogo.showModal()
    try {
      mias = (await pb.collection("observaciones").getList(1, 5, {
        filter: pb.filter("usuario = {:u}", { u: sesion.usuario.id }),
        sort: "-created",
      })).items
    } catch {
      mias = []
    }
  }

  async function guardar(evento) {
    evento.preventDefault()
    guardando = true
    error = ""
    try {
      const nueva = await pb.collection("observaciones").create({ tipo, texto, pantalla })
      mias = [nueva, ...mias].slice(0, 5)
      texto = ""
      aviso = "Guardado. Gracias: el administrador lo va a revisar."
    } catch (err) {
      error = mensajeError(err)
    } finally {
      guardando = false
    }
  }

  const fecha = (iso) => new Date(iso.replace(" ", "T")).toLocaleDateString("es")
</script>

<button class="boton secundario anotar" onclick={abrir}>Anotar un problema</button>

<dialog bind:this={dialogo} aria-labelledby="titulo-observacion">
  <form onsubmit={guardar}>
    <h2 id="titulo-observacion">Anotar para mejorar el sistema</h2>
    <p class="suave">
      Escriba qué pasó o qué le gustaría. Se guarda junto con la pantalla donde está, así se puede revisar después.
    </p>
    <fieldset>
      <legend class="oculto">Tipo</legend>
      {#each Object.entries(TIPOS) as [valor, etiqueta]}
        <label class="opcion"><input type="radio" name="tipo" value={valor} bind:group={tipo} /> {etiqueta}</label>
      {/each}
    </fieldset>
    <div class="campo">
      <label for="texto-observacion">Qué pasó</label>
      <textarea id="texto-observacion" bind:value={texto} rows="5" maxlength="3000" required
                placeholder="Por ejemplo: al guardar el acta 3 del folio 12 apareció un error."></textarea>
    </div>
    {#if aviso}<div class="alerta exito" role="status">{aviso}</div>{/if}
    {#if error}<div class="alerta error" role="alert">{error}</div>{/if}
    <div class="fila">
      <button class="boton" type="submit" disabled={guardando || !texto.trim()}>{guardando ? "Guardando…" : "Guardar"}</button>
      <button class="boton secundario" type="button" onclick={() => dialogo.close()}>Cerrar</button>
    </div>

    {#if mias.length}
      <h3>Mis últimas notas</h3>
      <ul class="mias">
        {#each mias as m (m.id)}
          <li>
            <span class="suave">{fecha(m.created)} ·</span> {m.texto}
            {#if m.estado === "resuelta"}
              <div class="respuesta"><strong>Resuelta.</strong> {m.respuesta}</div>
            {/if}
          </li>
        {/each}
      </ul>
    {/if}
  </form>
</dialog>

<style>
  .anotar { font-size: 0.9rem; min-height: 36px; padding: 0.3rem 0.75rem; }
  dialog {
    width: min(560px, calc(100vw - 2rem)); max-height: calc(100vh - 2rem); overflow-y: auto;
    border: 1px solid var(--borde); border-radius: var(--radio); padding: 1.5rem; color: var(--texto);
  }
  dialog::backdrop { background: rgb(0 0 0 / 0.4); }
  fieldset { border: 0; padding: 0; margin: 0 0 1rem; display: flex; flex-wrap: wrap; gap: 0.5rem 1.25rem; }
  .opcion { display: flex; gap: 0.4rem; align-items: center; }
  .opcion input { width: 18px; height: 18px; }
  .oculto { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }
  textarea { resize: vertical; }
  h3 { font-size: 1rem; margin: 1.25rem 0 0.5rem; }
  .mias { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.92rem; }
  .respuesta { color: var(--exito); }
</style>
