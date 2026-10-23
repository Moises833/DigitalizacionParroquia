<script>
  import { onMount, untrack } from "svelte"
  import { pb, mensajeError } from "../lib/pb.js"
  import { esAdmin } from "../lib/sesion.svelte.js"
  import { urlArchivo } from "../lib/archivos.svelte.js"

  let { pagina, foliosOcupados, alCerrar } = $props()

  let dialogo
  let folio = $state(untrack(() => pagina.folio)) // copia editable del folio
  let error = $state("")
  let guardando = $state(false)

  async function guardar(evento) {
    evento.preventDefault()
    const nuevo = folio.trim()
    error = ""
    if (!nuevo) return (error = "El folio no puede quedar vacío.")
    if (nuevo === pagina.folio) return alCerrar()
    if (foliosOcupados.includes(nuevo)) return (error = "Ese folio ya existe en el libro.")
    guardando = true
    try {
      await pb.collection("paginas").update(pagina.id, { folio: nuevo })
      alCerrar()
    } catch (err) {
      error = mensajeError(err)
    } finally {
      guardando = false
    }
  }

  async function eliminar() {
    if (!confirm(`¿Eliminar la página del folio ${pagina.folio}? Esta acción no se puede deshacer.`)) return
    try {
      await pb.collection("paginas").delete(pagina.id)
      alCerrar()
    } catch (err) {
      error = err?.status === 400
        ? "No se puede eliminar: la página ya tiene actas registradas."
        : mensajeError(err)
    }
  }

  onMount(() => dialogo.showModal())
</script>

<dialog bind:this={dialogo} onclose={alCerrar} aria-labelledby="titulo-visor">
  <div class="cabecera">
    <h2 id="titulo-visor">Folio {pagina.folio}</h2>
    <button class="boton secundario" onclick={() => dialogo.close()}>Cerrar</button>
  </div>

  <div class="imagen">
    <img src={urlArchivo(pagina, pagina.imagen)} alt="Página del folio {pagina.folio}" />
  </div>

  {#if error}<div class="alerta error" role="alert">{error}</div>{/if}

  <form class="fila" onsubmit={guardar}>
    <div class="campo">
      <label for="folio-visor">Folio</label>
      <input id="folio-visor" bind:value={folio} maxlength="20" />
    </div>
    <button class="boton" type="submit" disabled={guardando}>Guardar folio</button>
    <a class="boton secundario" href={urlArchivo(pagina, pagina.imagen)} target="_blank" rel="noopener">Ver en tamaño completo</a>
    <span class="espaciador"></span>
    {#if esAdmin()}
      <button class="boton peligro" type="button" onclick={eliminar}>Eliminar página</button>
    {/if}
  </form>
</dialog>

<style>
  dialog {
    width: min(900px, calc(100vw - 2rem)); max-height: calc(100vh - 2rem);
    border: 0; border-radius: var(--radio); padding: 1.25rem; box-shadow: 0 10px 40px rgb(0 0 0 / 0.25);
  }
  dialog::backdrop { background: rgb(0 0 0 / 0.55); }
  .cabecera { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 0.75rem; }
  .cabecera h2 { margin: 0; }
  .imagen { background: var(--fondo); border-radius: var(--radio); margin-bottom: 1rem; text-align: center; }
  .imagen img { max-width: 100%; max-height: 60vh; object-fit: contain; }
  form { align-items: flex-end; }
  form .campo { flex: 0 1 140px; margin-bottom: 0; }
  a.boton { text-decoration: none; }
</style>
