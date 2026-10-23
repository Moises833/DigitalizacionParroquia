<script>
  import { onMount } from "svelte"
  import { prepararPagina, formatoTamano } from "../lib/imagen.js"

  // Pantalla del teléfono: se abre desde el QR y no requiere cuenta; el token autoriza.
  let { token } = $props()

  let sesion = $state(null)
  let estado = $state("cargando") // cargando | listo | invalida | sin-conexion
  let enviadas = $state([]) // { id, nombre, folio, estado, progreso, mensaje, tamano }
  let siguienteId = 0
  let cola = []
  let procesando = false

  let pendientes = $derived(enviadas.filter((e) => e.estado !== "listo" && e.estado !== "error").length)

  async function cargarSesion() {
    try {
      const r = await fetch(`/api/captura/movil/${encodeURIComponent(token)}`)
      if (r.status === 404) return (estado = "invalida")
      if (!r.ok) throw new Error()
      sesion = await r.json()
      estado = "listo"
    } catch {
      estado = "sin-conexion"
    }
  }

  // fetch no informa el progreso de subida: con Wi-Fi débil conviene verlo.
  function subirConProgreso({ imagen, miniatura }, alProgreso) {
    return new Promise((resolver, rechazar) => {
      const xhr = new XMLHttpRequest()
      xhr.open("POST", `/api/captura/movil/${encodeURIComponent(token)}/paginas`)
      xhr.upload.onprogress = (e) => e.lengthComputable && alProgreso(e.loaded / e.total)
      xhr.onload = () => {
        let cuerpo = {}
        try { cuerpo = JSON.parse(xhr.responseText) } catch { /* respuesta vacía */ }
        if (xhr.status === 201) resolver(cuerpo)
        else rechazar(Object.assign(new Error(cuerpo.message || "No se pudo guardar la foto."), { status: xhr.status }))
      }
      xhr.onerror = () => rechazar(Object.assign(new Error("Se perdió la conexión con la computadora."), { status: 0 }))
      const datos = new FormData()
      datos.append("imagen", imagen)
      datos.append("miniatura", miniatura)
      xhr.send(datos)
    })
  }

  async function procesarCola() {
    if (procesando) return
    procesando = true
    while (cola.length) {
      const item = cola.shift()
      try {
        item.estado = "comprimiendo"
        const pagina = await prepararPagina(item.archivo)
        item.archivo = null // libera la original de la memoria
        item.tamano = pagina.imagen.size
        item.estado = "subiendo"
        const r = await subirConProgreso(pagina, (p) => (item.progreso = p))
        item.folio = r.folio
        item.estado = "listo"
        sesion.folio_siguiente = r.folio_siguiente
        sesion.fotos_recibidas += 1
      } catch (err) {
        item.estado = "error"
        item.mensaje = err.message
        if (err.status === 404) {
          estado = "invalida"
          cola = []
        }
      }
    }
    procesando = false
  }

  function agregar(archivos) {
    for (const archivo of archivos) {
      const item = { id: ++siguienteId, nombre: archivo.name, archivo, folio: "", estado: "espera", progreso: 0, mensaje: "", tamano: archivo.size }
      enviadas.unshift(item)
      cola.push(enviadas[0]) // la versión reactiva del objeto
    }
    procesarCola()
  }

  function reintentar(item) {
    // La foto original ya no está en memoria: se pide tomarla de nuevo.
    enviadas = enviadas.filter((e) => e.id !== item.id)
  }

  onMount(() => {
    cargarSesion()
    // Al volver a la pestaña se actualiza el folio (pudo cambiarse desde la PC).
    const alVolver = () => document.visibilityState === "visible" && estado === "listo" && !procesando && cargarSesion()
    document.addEventListener("visibilitychange", alVolver)
    return () => document.removeEventListener("visibilitychange", alVolver)
  })
</script>

<div class="movil">
  {#if estado === "cargando"}
    <p class="centro suave">Conectando con la computadora…</p>
  {:else if estado === "invalida"}
    <div class="centro">
      <h1>Vinculación terminada</h1>
      <p>Este código ya no es válido. En la computadora, abra <strong>Captura de páginas → Usar el teléfono</strong> y escanee el código nuevo.</p>
    </div>
  {:else if estado === "sin-conexion"}
    <div class="centro">
      <h1>Sin conexión</h1>
      <p>Revise que el teléfono esté en la misma red Wi-Fi que la computadora.</p>
      <button class="boton" onclick={cargarSesion}>Reintentar</button>
    </div>
  {:else}
    <header>
      <div class="suave">Libro</div>
      <h1>{sesion.libro.numero}</h1>
      <div class="datos">
        <span>Próximo folio: <strong>{sesion.folio_siguiente}</strong></span>
        <span>Enviadas: <strong>{sesion.fotos_recibidas}</strong></span>
      </div>
    </header>

    <div class="botones">
      <label class="boton grande">
        <input type="file" accept="image/*" capture="environment"
               onchange={(e) => { agregar(e.currentTarget.files); e.currentTarget.value = "" }} />
        Tomar foto
      </label>
      <label class="boton secundario">
        <input type="file" accept="image/*" multiple
               onchange={(e) => { agregar(e.currentTarget.files); e.currentTarget.value = "" }} />
        Elegir de la galería
      </label>
    </div>

    <p class="consejo suave">Fotografíe una página por foto, con buena luz, de frente y sin sombras sobre el texto.</p>

    {#if enviadas.length}
      <ul class="lista" aria-live="polite">
        {#each enviadas as item (item.id)}
          <li class={item.estado}>
            <div>
              {#if item.estado === "listo"}
                <strong>Folio {item.folio}</strong> <span class="suave">enviado · {formatoTamano(item.tamano)}</span>
              {:else if item.estado === "error"}
                <strong>No se envió</strong> <span class="mensaje">{item.mensaje}</span>
              {:else if item.estado === "subiendo"}
                Enviando… {Math.round(item.progreso * 100)}%
              {:else if item.estado === "comprimiendo"}
                Preparando foto…
              {:else}
                En espera
              {/if}
            </div>
            {#if item.estado === "subiendo"}
              <progress max="1" value={item.progreso}></progress>
            {:else if item.estado === "error"}
              <button class="boton secundario chico" onclick={() => reintentar(item)}>Descartar</button>
            {/if}
          </li>
        {/each}
      </ul>
      {#if pendientes}<p class="suave">No cierre esta página hasta que terminen de enviarse.</p>{/if}
    {/if}
  {/if}
</div>

<style>
  .movil { max-width: 520px; margin: 0 auto; padding: 1rem; min-height: 100vh; }
  .centro { min-height: 80vh; display: flex; flex-direction: column; justify-content: center; gap: 0.5rem; }
  header { background: var(--primario); color: #fff; border-radius: var(--radio); padding: 1rem 1.25rem; margin-bottom: 1rem; }
  header .suave { color: rgb(255 255 255 / 0.75); font-size: 0.9rem; }
  header h1 { margin: 0 0 0.5rem; }
  .datos { display: flex; gap: 1.5rem; flex-wrap: wrap; }
  .botones { display: flex; flex-direction: column; gap: 0.75rem; }
  .botones label { position: relative; }
  .botones input { position: absolute; inset: 0; opacity: 0; width: 100%; cursor: pointer; }
  .grande { min-height: 72px; font-size: 1.3rem; }
  .consejo { font-size: 0.9rem; margin: 1rem 0; }
  .lista { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem; }
  .lista li {
    background: var(--superficie); border: 1px solid var(--borde); border-radius: var(--radio);
    padding: 0.6rem 0.8rem; display: flex; flex-direction: column; gap: 0.4rem;
  }
  .lista li.listo { border-left: 4px solid var(--exito); }
  .lista li.error { border-left: 4px solid var(--error); }
  .mensaje { color: var(--error); }
  progress { width: 100%; height: 8px; accent-color: var(--primario); }
  .chico { min-height: 34px; padding: 0.2rem 0.7rem; align-self: flex-start; }
</style>
