<script>
  import { onDestroy } from "svelte"
  import { pb, mensajeError } from "../lib/pb.js"
  import { prepararPagina, formatoTamano, compararFolios } from "../lib/imagen.js"

  let { libro, folioSugerido, foliosOcupados } = $props()

  const SIMULTANEAS = 2
  const ETIQUETAS = {
    cola: "En espera",
    comprimiendo: "Comprimiendo…",
    subiendo: "Subiendo…",
    listo: "Subida",
    error: "Error",
  }

  let filas = $state([])
  let subiendo = $state(false)
  let arrastrando = $state(false)
  let resumen = $state("")
  let siguienteId = 0

  let pendientes = $derived(filas.filter((f) => f.estado === "cola" || f.estado === "error"))
  let terminadas = $derived(filas.filter((f) => f.estado === "listo").length)

  function proximoFolio() {
    const usados = filas.map((f) => parseInt(f.folio, 10) || 0)
    return Math.max(folioSugerido - 1, ...usados) + 1
  }

  function agregar(lista) {
    resumen = ""
    const imagenes = [...lista]
      .filter((a) => a.type.startsWith("image/") || /\.(jpe?g|png|webp|heic)$/i.test(a.name))
      .sort((a, b) => compararFolios(a.name, b.name)) // IMG_0012 antes que IMG_0103
    let folio = proximoFolio()
    for (const archivo of imagenes) {
      filas.push({
        id: ++siguienteId,
        archivo,
        vista: URL.createObjectURL(archivo),
        folio: String(folio++),
        estado: "cola",
        mensaje: "",
        tamano: archivo.size,
      })
    }
  }

  function quitar(fila) {
    URL.revokeObjectURL(fila.vista)
    filas = filas.filter((f) => f.id !== fila.id)
  }

  function limpiarSubidas() {
    for (const f of filas) if (f.estado === "listo") URL.revokeObjectURL(f.vista)
    filas = filas.filter((f) => f.estado !== "listo")
    resumen = ""
  }

  // Marca como error los folios vacíos, repetidos en la lista o ya usados en el libro.
  function validarFolios() {
    const conteo = new Map()
    for (const f of pendientes) conteo.set(f.folio.trim(), (conteo.get(f.folio.trim()) ?? 0) + 1)
    let validas = true
    for (const f of pendientes) {
      const folio = f.folio.trim()
      f.estado = "cola"
      f.mensaje = ""
      if (!folio) f.mensaje = "Falta el folio."
      else if (conteo.get(folio) > 1) f.mensaje = "Folio repetido en la lista."
      else if (foliosOcupados.includes(folio)) f.mensaje = "Ese folio ya existe en el libro."
      if (f.mensaje) {
        f.estado = "error"
        validas = false
      }
    }
    return validas
  }

  async function subirFila(fila) {
    try {
      fila.estado = "comprimiendo"
      const { imagen, miniatura } = await prepararPagina(fila.archivo)
      fila.tamano = imagen.size
      fila.estado = "subiendo"
      const datos = new FormData()
      datos.append("libro", libro.id)
      datos.append("folio", fila.folio.trim())
      datos.append("estado", "pendiente")
      datos.append("origen", "pc")
      datos.append("imagen", imagen)
      datos.append("miniatura", miniatura)
      await pb.collection("paginas").create(datos)
      fila.estado = "listo"
    } catch (err) {
      fila.estado = "error"
      fila.mensaje = err?.response?.data?.folio?.code === "validation_not_unique"
        ? "Ese folio ya existe en el libro."
        : err instanceof Error && !err.response ? err.message : mensajeError(err)
    }
  }

  async function subirTodo() {
    if (!validarFolios()) {
      resumen = "Corrija los folios marcados antes de subir."
      return
    }
    subiendo = true
    resumen = ""
    const cola = [...pendientes]
    const trabajador = async () => {
      while (cola.length) await subirFila(cola.shift())
    }
    await Promise.all(Array.from({ length: SIMULTANEAS }, trabajador))
    subiendo = false
    const fallidas = filas.filter((f) => f.estado === "error").length
    resumen = fallidas
      ? `${terminadas} páginas subidas, ${fallidas} con error.`
      : `${terminadas} páginas subidas.`
  }

  function soltar(evento) {
    evento.preventDefault()
    arrastrando = false
    if (!subiendo) agregar(evento.dataTransfer.files)
  }

  onDestroy(() => {
    for (const f of filas) URL.revokeObjectURL(f.vista)
  })
</script>

<section class="tarjeta">
  <h2>Subir fotos desde esta PC</h2>

  <label class="zona" class:arrastrando
         ondragover={(e) => { e.preventDefault(); arrastrando = true }}
         ondragleave={() => (arrastrando = false)}
         ondrop={soltar}>
    <input type="file" accept="image/*" multiple disabled={subiendo}
           onchange={(e) => { agregar(e.currentTarget.files); e.currentTarget.value = "" }} />
    <strong>Arrastre aquí las fotos o haga clic para elegirlas</strong>
    <span class="suave">Puede elegir muchas a la vez. Se numeran en orden de nombre de archivo a partir del folio {proximoFolio()}.</span>
  </label>

  {#if filas.length}
    <div class="tabla-contenedor">
      <table class="tabla">
        <thead><tr><th>Foto</th><th>Folio</th><th>Estado</th><th></th></tr></thead>
        <tbody>
          {#each filas as fila (fila.id)}
            <tr class={fila.estado}>
              <td class="foto">
                <img src={fila.vista} alt="" loading="lazy" />
                <span class="nombre">{fila.archivo.name}<br /><span class="suave">{formatoTamano(fila.tamano)}</span></span>
              </td>
              <td>
                <input class="folio" aria-label="Folio de {fila.archivo.name}" bind:value={fila.folio}
                       disabled={subiendo || fila.estado === "listo"} maxlength="20" />
              </td>
              <td>
                <span class="estado">{ETIQUETAS[fila.estado]}</span>
                {#if fila.mensaje}<br /><span class="mensaje">{fila.mensaje}</span>{/if}
              </td>
              <td>
                {#if !subiendo && fila.estado !== "listo"}
                  <button class="boton secundario chico" onclick={() => quitar(fila)}>Quitar</button>
                {/if}
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>

    <div class="fila acciones">
      <button class="boton" onclick={subirTodo} disabled={subiendo || pendientes.length === 0}>
        {subiendo ? `Subiendo… (${terminadas}/${filas.length})` : `Subir ${pendientes.length} páginas`}
      </button>
      {#if terminadas && !subiendo}
        <button class="boton secundario" onclick={limpiarSubidas}>Quitar las ya subidas</button>
      {/if}
      {#if resumen}<span role="status">{resumen}</span>{/if}
    </div>
  {/if}
</section>

<style>
  section { margin-bottom: 1.5rem; }
  .zona {
    display: flex; flex-direction: column; align-items: center; gap: 0.3rem; text-align: center;
    padding: 2rem 1rem; border: 2px dashed var(--borde); border-radius: var(--radio);
    cursor: pointer; background: var(--fondo); margin-bottom: 1rem;
  }
  .zona:hover, .zona.arrastrando { border-color: var(--primario); background: var(--primario-suave); }
  .zona input { position: absolute; width: 1px; height: 1px; opacity: 0; }
  .zona:focus-within { outline: 3px solid var(--primario-suave); }
  .foto { display: flex; gap: 0.75rem; align-items: center; }
  .foto img { width: 56px; height: 72px; object-fit: cover; border-radius: 4px; border: 1px solid var(--borde); }
  .nombre { font-size: 0.9rem; word-break: break-all; }
  .folio { width: 6rem; min-height: 38px; padding: 0.3rem 0.5rem; border: 1px solid var(--borde); border-radius: var(--radio); }
  tr.listo .estado { color: var(--exito); font-weight: 600; }
  tr.error .estado, .mensaje { color: var(--error); }
  tr.error .estado { font-weight: 600; }
  .mensaje { font-size: 0.85rem; }
  .chico { min-height: 34px; padding: 0.2rem 0.7rem; }
  .acciones { margin-top: 1rem; }
</style>
