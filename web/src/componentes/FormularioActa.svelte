<script>
  import { untrack } from "svelte"
  import { pb, mensajeError } from "../lib/pb.js"
  import { esAdmin } from "../lib/sesion.svelte.js"
  import { recortarImagen, mismoRecorte } from "../lib/recorte.js"
  import { propuestaADatos, CAMPOS_TEXTO as TEXTOS, CAMPOS_FECHA as FECHAS } from "../lib/propuesta.js"
  import { AVISOS } from "../lib/marcas.js"
  import CampoFecha from "./CampoFecha.svelte"

  // El componente se vuelve a crear para cada recuadro ({#key} en el padre).
  // propuesta: lo que leyó la IA para este recuadro (solo se usa si aún no hay acta).
  let {
    pagina, posicion, segmento, acta = null, anterior = null, propuesta = null,
    urlImagen, proporcionImagen = 0.75,
    sugerencias = { ministro: [], lugar_nacimiento: [] }, sucio = $bindable(false),
    alGuardado, alEliminado,
  } = $props()

  // Lo que suele repetirse entre actas seguidas del mismo libro.
  const ARRASTRABLES = ["ministro", "lugar_nacimiento", "fecha_bautizo"]

  function datosIniciales() {
    const d = Object.fromEntries([...TEXTOS, ...FECHAS].map((k) => [k, acta?.[k] ?? ""]))
    // Todas las claves presentes: bind: no acepta undefined en props con valor por defecto.
    const marcas = Object.fromEntries([...TEXTOS, ...FECHAS].map((k) => [k, ""]))
    if (acta) return { d, marcas }

    if (propuesta) {
      const ia = propuestaADatos(propuesta)
      Object.assign(d, ia.datos)
      Object.assign(marcas, ia.marcas)
    }
    // Lo que la IA no leyó se completa con el acta anterior.
    if (anterior) {
      for (const k of ARRASTRABLES) {
        if (!d[k] && anterior[k]) {
          d[k] = anterior[k]
          marcas[k] = "copiado"
        }
      }
      if (!d.numero_acta && /^\d+$/.test(anterior.numero_acta ?? "")) {
        d.numero_acta = String(parseInt(anterior.numero_acta, 10) + 1)
        marcas.numero_acta = "copiado"
      }
    }
    return { d, marcas }
  }

  const inicio = untrack(datosIniciales)
  let datos = $state(inicio.d)
  let marcas = $state(inicio.marcas)
  let invalidas = $state({ fecha_nacimiento: false, fecha_bautizo: false })
  let revisada = $state(untrack(() => acta?.estado === "revisada" || acta?.estado === "verificada"))
  let zoom = $state(1)
  let guardando = $state(false)
  let error = $state("")

  let fechasInvertidas = $derived(
    datos.fecha_nacimiento && datos.fecha_bautizo && datos.fecha_nacimiento > datos.fecha_bautizo,
  )
  let pendientes = $derived(Object.values(marcas).filter(Boolean))

  // Un acta nueva con datos copiados no cuenta como cambio hasta que se escribe algo:
  // "sucio" lo marca el evento input de todo el formulario.
  function editado(campo) {
    marcas[campo] = ""
  }

  // Recorte mostrado con la imagen completa desplazada: no hace falta otra descarga.
  let estiloRecorte = $derived(
    `aspect-ratio:${(segmento.ancho / segmento.alto) * proporcionImagen};width:${zoom * 100}%`,
  )
  let estiloImagen = $derived(
    `width:${100 / segmento.ancho}%;left:${(-segmento.x / segmento.ancho) * 100}%;top:${(-segmento.y / segmento.alto) * 100}%`,
  )

  async function guardar(seguir) {
    error = ""
    if (!datos.nombres.trim()) return (error = "Escriba el nombre del bautizado.")
    if (invalidas.fecha_nacimiento || invalidas.fecha_bautizo) {
      return (error = "Hay una fecha que no se reconoce. Corríjala o déjela vacía.")
    }
    if (fechasInvertidas && !confirm("La fecha de nacimiento es posterior a la de bautizo. ¿Guardar de todos modos?")) return

    guardando = true
    try {
      const datosForm = new FormData()
      for (const k of [...TEXTOS, ...FECHAS]) datosForm.append(k, (datos[k] ?? "").trim())
      datosForm.append("estado", revisada ? (acta?.estado === "verificada" ? "verificada" : "revisada") : "borrador")
      if (!mismoRecorte(acta?.recorte, segmento)) {
        datosForm.append("recorte", JSON.stringify(segmento))
        datosForm.append("imagen_recorte", await recortarImagen(urlImagen, segmento))
      }
      let registro
      if (acta) {
        registro = await pb.collection("actas_bautismo").update(acta.id, datosForm)
      } else {
        datosForm.append("pagina", pagina.id)
        datosForm.append("libro", pagina.libro)
        datosForm.append("posicion", String(posicion))
        datosForm.append("origen_datos", propuesta ? "ia" : "manual")
        if (propuesta) datosForm.append("ia_respuesta", JSON.stringify(propuesta))
        registro = await pb.collection("actas_bautismo").create(datosForm)
      }
      sucio = false
      alGuardado(registro, seguir)
    } catch (err) {
      error = mensajeError(err)
    } finally {
      guardando = false
    }
  }

  async function eliminar() {
    if (!confirm(`¿Eliminar el acta ${posicion} de esta página? Esta acción no se puede deshacer.`)) return
    try {
      await pb.collection("actas_bautismo").delete(acta.id)
      sucio = false
      alEliminado(acta)
    } catch (err) {
      error = mensajeError(err)
    }
  }

  function teclas(evento) {
    if (evento.key === "Enter" && (evento.ctrlKey || evento.metaKey)) {
      evento.preventDefault()
      if (!guardando) guardar(true)
    }
  }
</script>

{#snippet texto(campo, etiqueta, opciones = {})}
  <div class="campo marca-{marcas[campo] || 'ninguna'}">
    <label for="acta-{campo}">{etiqueta}</label>
    {#if opciones.largo}
      <textarea id="acta-{campo}" rows="2" bind:value={datos[campo]} oninput={() => editado(campo)}></textarea>
    {:else}
      <input id="acta-{campo}" bind:value={datos[campo]} oninput={() => editado(campo)}
             list={opciones.lista} autocomplete="off" maxlength={opciones.max ?? 200} />
    {/if}
    {#if marcas[campo]}<span class="ayuda">{AVISOS[marcas[campo]]}</span>{/if}
  </div>
{/snippet}

<!-- svelte-ignore a11y_no_noninteractive_element_interactions -->
<form onsubmit={(e) => { e.preventDefault(); guardar(false) }} onkeydown={teclas} oninput={() => (sucio = true)}>
  <div class="cabecera">
    <h2>Acta {posicion} {#if acta}<span class="etiqueta {acta.estado}">{acta.estado === "borrador" ? "Borrador" : acta.estado === "verificada" ? "Verificada" : "Revisada"}</span>{:else}<span class="etiqueta nueva">Nueva</span>{/if}</h2>
    <div class="zoom" role="group" aria-label="Acercar el recorte">
      <button type="button" class="boton secundario" onclick={() => (zoom = Math.max(1, zoom - 0.5))} disabled={zoom <= 1} aria-label="Alejar">−</button>
      <span class="suave">{Math.round(zoom * 100)}%</span>
      <button type="button" class="boton secundario" onclick={() => (zoom = Math.min(4, zoom + 0.5))} disabled={zoom >= 4} aria-label="Acercar">+</button>
    </div>
  </div>

  <div class="visor">
    <div class="recorte" style={estiloRecorte}>
      <img src={urlImagen} alt="Acta {posicion} tal como está en el libro" style={estiloImagen} draggable="false" />
    </div>
  </div>

  {#if propuesta && !acta}
    <p class="aviso-copia ia">
      La IA leyó esta acta. Compare cada campo resaltado con la imagen antes de guardar;
      {#if pendientes.includes("dudoso")}<strong>los marcados en rojo son los que la IA no pudo leer con seguridad.</strong>{/if}
    </p>
    {#if propuesta.texto_completo}
      <details class="texto-ia">
        <summary>Texto completo leído por la IA</summary>
        <p>{propuesta.texto_completo}</p>
      </details>
    {/if}
  {:else if pendientes.length}
    <p class="aviso-copia">Los campos resaltados se copiaron del acta anterior. Revíselos antes de guardar.</p>
  {/if}

  <fieldset>
    <legend>Registro</legend>
    <div class="rejilla">
      {@render texto("numero_acta", "N.º de acta", { max: 20 })}
      <CampoFecha id="acta-fecha_bautizo" etiqueta="Fecha de bautizo" bind:valor={datos.fecha_bautizo}
                  bind:marca={marcas.fecha_bautizo} bind:invalida={invalidas.fecha_bautizo} />
      {@render texto("ministro", "Ministro", { lista: "sug-ministro" })}
    </div>
  </fieldset>

  <fieldset>
    <legend>Bautizado</legend>
    <div class="rejilla">
      {@render texto("nombres", "Nombres *", { max: 150 })}
      {@render texto("apellidos", "Apellidos", { max: 150 })}
      <div class="campo marca-{marcas.sexo || 'ninguna'}">
        <span class="etiqueta-campo" id="acta-sexo">Sexo</span>
        <div class="opciones" role="radiogroup" aria-labelledby="acta-sexo">
          {#each [["M", "Masculino"], ["F", "Femenino"], ["", "Sin dato"]] as [valor, nombre]}
            <label><input type="radio" name="sexo" value={valor} bind:group={datos.sexo} onchange={() => editado("sexo")} /> {nombre}</label>
          {/each}
        </div>
        {#if marcas.sexo}<span class="ayuda">{AVISOS[marcas.sexo]}</span>{/if}
      </div>
      <CampoFecha id="acta-fecha_nacimiento" etiqueta="Fecha de nacimiento" bind:valor={datos.fecha_nacimiento}
                  bind:marca={marcas.fecha_nacimiento} bind:invalida={invalidas.fecha_nacimiento} />
      {@render texto("lugar_nacimiento", "Lugar de nacimiento", { lista: "sug-lugar" })}
    </div>
    {#if fechasInvertidas}
      <p class="alerta error">La fecha de nacimiento es posterior a la de bautizo.</p>
    {/if}
  </fieldset>

  <fieldset>
    <legend>Padres y padrinos</legend>
    <div class="rejilla">
      {@render texto("padre", "Padre")}
      {@render texto("madre", "Madre")}
      {@render texto("padrino", "Padrino")}
      {@render texto("madrina", "Madrina")}
    </div>
  </fieldset>

  <fieldset>
    <legend>Anotaciones</legend>
    {@render texto("registro_civil", "N.º de registro civil")}
    {@render texto("notas_marginales", "Notas marginales", { largo: true })}
    {@render texto("observaciones", "Observaciones", { largo: true })}
  </fieldset>

  <datalist id="sug-ministro">{#each sugerencias.ministro as s}<option value={s}></option>{/each}</datalist>
  <datalist id="sug-lugar">{#each sugerencias.lugar_nacimiento as s}<option value={s}></option>{/each}</datalist>

  <label class="revisada">
    <input type="checkbox" bind:checked={revisada} />
    Revisada: los datos coinciden con el libro
  </label>

  {#if error}<div class="alerta error" role="alert">{error}</div>{/if}

  <div class="fila acciones">
    <button class="boton" type="button" onclick={() => guardar(true)} disabled={guardando}>
      {guardando ? "Guardando…" : "Guardar y seguir"}
    </button>
    <button class="boton secundario" type="submit" disabled={guardando}>Guardar</button>
    <span class="suave atajo">Ctrl + Enter: guardar y seguir</span>
    <span class="espaciador"></span>
    {#if acta && acta.estado !== "borrador"}
      <a class="boton secundario" href="#/certificado/{acta.id}">Fe de bautismo</a>
    {/if}
    {#if acta && esAdmin()}
      <button class="boton peligro" type="button" onclick={eliminar}>Eliminar acta</button>
    {/if}
  </div>
</form>

<style>
  .cabecera { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 0.5rem; }
  .cabecera h2 { margin: 0; display: flex; align-items: center; gap: 0.5rem; }
  .zoom { display: flex; align-items: center; gap: 0.4rem; }
  .zoom .boton { min-height: 34px; padding: 0.2rem 0.7rem; }

  .visor {
    max-height: 42vh; overflow: auto; border: 1px solid var(--borde); border-radius: var(--radio);
    background: var(--fondo); margin-bottom: 0.75rem;
  }
  .recorte { position: relative; overflow: hidden; }
  .recorte img { position: absolute; max-width: none; height: auto; user-select: none; }

  .etiqueta {
    font-family: var(--fuente); font-size: 0.8rem; font-weight: 600; padding: 0.1rem 0.5rem;
    border-radius: 999px; background: var(--fondo); color: var(--texto-suave);
  }
  .etiqueta.nueva { background: #fdf1dc; color: #7a4a0c; }
  .etiqueta.revisada, .etiqueta.verificada { background: #e8f3ea; color: var(--exito); }

  fieldset { border: 1px solid var(--borde); border-radius: var(--radio); padding: 0.75rem 1rem 0; margin: 0 0 1rem; }
  legend { font-weight: 700; padding: 0 0.35rem; color: var(--primario); }
  .rejilla { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); column-gap: 1rem; }
  .campo textarea { resize: vertical; }
  .etiqueta-campo { font-weight: 600; font-size: 0.95rem; }
  .opciones { display: flex; flex-wrap: wrap; gap: 0.25rem 1rem; min-height: 42px; align-items: center; }
  .opciones label { font-weight: 400; display: flex; gap: 0.3rem; align-items: center; }

  :global(.campo.marca-copiado input), :global(.campo.marca-ia input), :global(.campo.marca-ia textarea) {
    background: #fdf6e3; border-color: #d9b45a;
  }
  :global(.campo.marca-dudoso input), :global(.campo.marca-dudoso textarea) {
    background: #fdecea; border-color: var(--error); border-width: 2px;
  }
  :global(.campo.marca-dudoso .ayuda) { color: var(--error); font-weight: 600; }
  .aviso-copia { font-size: 0.9rem; background: #fdf6e3; border-left: 4px solid #d9b45a; padding: 0.4rem 0.75rem; margin: 0 0 0.75rem; }
  .texto-ia { margin: 0 0 0.75rem; font-size: 0.9rem; }
  .texto-ia summary { cursor: pointer; color: var(--primario); font-weight: 600; }
  .texto-ia p { white-space: pre-line; background: var(--fondo); padding: 0.5rem 0.75rem; border-radius: var(--radio); margin: 0.4rem 0 0; }

  .revisada { display: flex; gap: 0.5rem; align-items: center; font-weight: 600; margin-bottom: 1rem; }
  .revisada input { width: 20px; height: 20px; }
  .acciones { align-items: center; }
  .atajo { font-size: 0.85rem; }
</style>
