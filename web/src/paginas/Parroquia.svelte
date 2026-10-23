<script>
  import { onMount } from "svelte"
  import { pb, mensajeError } from "../lib/pb.js"
  import { prepararTokenArchivos, urlArchivo } from "../lib/archivos.svelte.js"

  const CAMPOS = [
    { id: "nombre", texto: "Nombre de la parroquia", requerido: true },
    { id: "diocesis", texto: "Diócesis o arquidiócesis" },
    { id: "parroco", texto: "Párroco" },
    { id: "direccion", texto: "Dirección" },
    { id: "ciudad", texto: "Ciudad" },
    { id: "telefono", texto: "Teléfono" },
  ]
  const IMAGENES = [
    { id: "membrete", texto: "Membrete" },
    { id: "sello", texto: "Sello" },
    { id: "firma", texto: "Firma del párroco" },
  ]

  let registro = $state(null)
  let datos = $state({})
  let archivos = {}
  let error = $state("")
  let aviso = $state("")
  let guardando = $state(false)

  async function cargar() {
    await prepararTokenArchivos()
    registro = (await pb.collection("parroquia").getList(1, 1)).items[0]
    datos = Object.fromEntries(CAMPOS.map((c) => [c.id, registro[c.id] ?? ""]))
  }

  function elegirArchivo(campo, evento) {
    archivos[campo] = evento.currentTarget.files[0]
  }

  async function guardar(evento) {
    evento.preventDefault()
    error = ""
    aviso = ""
    guardando = true
    try {
      const cuerpo = new FormData()
      for (const [clave, valor] of Object.entries(datos)) cuerpo.append(clave, valor)
      for (const [clave, archivo] of Object.entries(archivos)) if (archivo) cuerpo.append(clave, archivo)
      registro = await pb.collection("parroquia").update(registro.id, cuerpo)
      archivos = {}
      aviso = "Datos de la parroquia guardados."
    } catch (err) {
      error = mensajeError(err)
    } finally {
      guardando = false
    }
  }

  onMount(cargar)
</script>

<h1>Datos de la parroquia</h1>
<p class="suave">Estos datos aparecen en la fe de bautismo.</p>

{#if aviso}<div class="alerta exito" role="status">{aviso}</div>{/if}
{#if error}<div class="alerta error" role="alert">{error}</div>{/if}

{#if registro}
  <form class="tarjeta" onsubmit={guardar}>
    <div class="rejilla">
      {#each CAMPOS as c}
        <div class="campo">
          <label for={c.id}>{c.texto}</label>
          <input id={c.id} bind:value={datos[c.id]} required={c.requerido} />
        </div>
      {/each}
    </div>

    <h2>Imágenes para el certificado</h2>
    <div class="rejilla">
      {#each IMAGENES as img}
        <div class="campo">
          <label for={img.id}>{img.texto}</label>
          {#if registro[img.id]}
            <img class="vista" src={urlArchivo(registro, registro[img.id])} alt={img.texto} />
          {/if}
          <input id={img.id} type="file" accept="image/png,image/jpeg,image/webp"
                 onchange={(e) => elegirArchivo(img.id, e)} />
          <span class="ayuda">PNG con fondo transparente si es posible. Máximo 2 MB.</span>
        </div>
      {/each}
    </div>

    <button class="boton" type="submit" disabled={guardando}>{guardando ? "Guardando…" : "Guardar"}</button>
  </form>
{/if}

<style>
  .rejilla { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 0 1rem; }
  h2 { margin-top: 1rem; }
  .vista { max-width: 100%; max-height: 90px; object-fit: contain; border: 1px dashed var(--borde); border-radius: var(--radio); padding: 0.25rem; background: #fff; }
</style>
