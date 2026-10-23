<script>
  import { onMount } from "svelte"
  import { pb, mensajeError } from "../lib/pb.js"
  import { esAdmin } from "../lib/sesion.svelte.js"

  const vacio = () => ({ id: "", numero: "", anio_inicio: "", anio_fin: "", observaciones: "" })

  let libros = $state([])
  let form = $state(vacio())
  let editando = $state(false)
  let error = $state("")
  let aviso = $state("")
  let guardando = $state(false)

  async function cargar() {
    libros = await pb.collection("libros").getFullList({ sort: "anio_inicio,numero" })
  }

  function limpiarMensajes() {
    error = ""
    aviso = ""
  }

  function nuevo() {
    form = vacio()
    editando = true
    limpiarMensajes()
  }

  function editar(libro) {
    form = {
      id: libro.id,
      numero: libro.numero,
      anio_inicio: libro.anio_inicio || "",
      anio_fin: libro.anio_fin || "",
      observaciones: libro.observaciones,
    }
    editando = true
    limpiarMensajes()
  }

  async function guardar(evento) {
    evento.preventDefault()
    limpiarMensajes()
    guardando = true
    const datos = {
      numero: form.numero.trim(),
      sacramento: "bautismo",
      anio_inicio: form.anio_inicio || null,
      anio_fin: form.anio_fin || null,
      observaciones: form.observaciones,
    }
    try {
      if (form.id) await pb.collection("libros").update(form.id, datos)
      else await pb.collection("libros").create(datos)
      aviso = `Libro «${datos.numero}» guardado.`
      editando = false
      await cargar()
    } catch (err) {
      error = err?.response?.data?.numero?.code === "validation_not_unique"
        ? "Ya existe un libro con ese número."
        : mensajeError(err)
    } finally {
      guardando = false
    }
  }

  async function eliminar(libro) {
    if (!confirm(`¿Eliminar el libro «${libro.numero}»? Solo es posible si no tiene páginas ni actas.`)) return
    limpiarMensajes()
    try {
      await pb.collection("libros").delete(libro.id)
      aviso = `Libro «${libro.numero}» eliminado.`
      await cargar()
    } catch (err) {
      error = err?.status === 400
        ? "No se puede eliminar: el libro tiene páginas o actas asociadas."
        : mensajeError(err)
    }
  }

  onMount(cargar)
</script>

<div class="fila encabezado">
  <h1>Libros de bautismo</h1>
  <span class="espaciador"></span>
  {#if !editando}<button class="boton" onclick={nuevo}>Agregar libro</button>{/if}
</div>

{#if aviso}<div class="alerta exito" role="status">{aviso}</div>{/if}
{#if error}<div class="alerta error" role="alert">{error}</div>{/if}

{#if editando}
  <form class="tarjeta formulario" onsubmit={guardar}>
    <h2>{form.id ? "Editar libro" : "Nuevo libro"}</h2>
    <div class="fila">
      <div class="campo">
        <label for="numero">Número o nombre del libro</label>
        <input id="numero" bind:value={form.numero} required maxlength="50" placeholder="Ej.: Tomo 12" />
      </div>
      <div class="campo">
        <label for="inicio">Año de inicio</label>
        <input id="inicio" type="number" min="1500" max="2200" bind:value={form.anio_inicio} />
      </div>
      <div class="campo">
        <label for="fin">Año de cierre</label>
        <input id="fin" type="number" min="1500" max="2200" bind:value={form.anio_fin} />
        <span class="ayuda">Vacío si el libro sigue en uso.</span>
      </div>
    </div>
    <div class="campo">
      <label for="obs">Observaciones</label>
      <textarea id="obs" rows="2" bind:value={form.observaciones} maxlength="2000"></textarea>
    </div>
    <div class="fila">
      <button class="boton" type="submit" disabled={guardando}>{guardando ? "Guardando…" : "Guardar"}</button>
      <button class="boton secundario" type="button" onclick={() => (editando = false)}>Cancelar</button>
    </div>
  </form>
{/if}

{#if libros.length === 0}
  <p class="suave">Todavía no hay libros registrados.</p>
{:else}
  <div class="tabla-contenedor">
    <table class="tabla">
      <thead>
        <tr><th>Libro</th><th>Años</th><th>Observaciones</th><th></th></tr>
      </thead>
      <tbody>
        {#each libros as libro (libro.id)}
          <tr>
            <td><strong>{libro.numero}</strong></td>
            <td>{libro.anio_inicio || "—"} – {libro.anio_fin || "en uso"}</td>
            <td class="suave">{libro.observaciones}</td>
            <td class="acciones">
              <button class="boton secundario" onclick={() => editar(libro)}>Editar</button>
              {#if esAdmin()}
                <button class="boton peligro" onclick={() => eliminar(libro)}>Eliminar</button>
              {/if}
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
{/if}

<style>
  .encabezado { margin-bottom: 1rem; }
  .encabezado h1 { margin: 0; }
  .formulario { margin-bottom: 1.5rem; }
  .acciones { white-space: nowrap; text-align: right; }
  .acciones .boton { min-height: 36px; padding: 0.3rem 0.8rem; }
</style>
