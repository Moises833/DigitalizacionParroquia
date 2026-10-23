<script>
  import { onMount, tick } from "svelte"
  import { pb, mensajeError } from "../lib/pb.js"
  import { prepararTokenArchivos, urlArchivo } from "../lib/archivos.svelte.js"
  import { formatearFecha } from "../lib/fechas.js"
  import { impresionPreferida, recordarImpresion } from "../lib/preferencias.js"
  import {
    PAPELES, CARGOS, FINES, expedicionEnLetras, unirPersonas, nombreCompleto,
    laParroquia, referenciaLibro, etiquetaLibro, paraFines, hoy,
  } from "../lib/certificado.js"

  /** @type {{ actaId: string, certificadoId?: string }} */
  let { actaId, certificadoId = "" } = $props()

  const guardada = impresionPreferida()
  let papel = $state(PAPELES[guardada.papel] ? guardada.papel : "carta")
  let conImagenes = $state(guardada.conImagenes ?? true)

  let parroquia = $state(null)
  let vista = $state(null) // { datos, estado, pagina, posicion } del acta
  let emitido = $state(null) // certificado guardado
  let anteriores = $state([])
  let fines = $state("")
  let firmante = $state("")
  let cargo = $state("Párroco")
  let fecha = $state(hoy())
  let cargando = $state(true)
  let emitiendo = $state(false)
  let error = $state("")
  let hoja = $state(null)

  let datos = $derived(emitido?.datos ?? vista?.datos ?? null)
  let p = $derived(datos?.parroquia ?? {})
  let medidas = $derived(PAPELES[papel])
  let enBorrador = $derived(!emitido && vista?.estado === "borrador")
  // Lo que se imprime: lo elegido al emitir, o lo que se está eligiendo.
  let impreso = $derived(emitido
    ? { fines: emitido.fines, firmante: emitido.firmante, cargo: emitido.cargo, fecha: emitido.fecha_expedicion }
    : { fines, firmante, cargo, fecha })
  let imagen = $derived({
    membrete: parroquia ? urlArchivo(parroquia, parroquia.membrete) : "",
    sello: conImagenes && parroquia ? urlArchivo(parroquia, parroquia.sello) : "",
    firma: conImagenes && parroquia ? urlArchivo(parroquia, parroquia.firma) : "",
  })
  let filas = $derived(datos ? [
    ["Fecha de bautismo", formatearFecha(datos.fecha_bautizo)],
    ["Fecha de nacimiento", formatearFecha(datos.fecha_nacimiento)],
    ["Lugar de nacimiento", datos.lugar_nacimiento],
    ["Padres", unirPersonas(datos.padre, datos.madre)],
    ["Padrinos", unirPersonas(datos.padrino, datos.madrina)],
    ["Ministro", datos.ministro],
    ["Registro civil", datos.registro_civil],
  ].filter(([, valor]) => valor) : [])
  let paginaCSS = $derived(`<style>@page { size: ${medidas.ancho}mm ${medidas.alto}mm; margin: 15mm 20mm; }</style>`)

  async function cargarAnteriores() {
    anteriores = await pb.collection("certificados").getFullList({
      filter: pb.filter("acta = {:acta}", { acta: actaId }),
      sort: "-anio,-correlativo",
      expand: "emitido_por",
      fields: "id,numero,fines,fecha_expedicion,created,expand.emitido_por.name,expand.emitido_por.email",
    })
  }

  onMount(async () => {
    try {
      await prepararTokenArchivos()
      parroquia = (await pb.collection("parroquia").getList(1, 1)).items[0] ?? null
      if (certificadoId) {
        emitido = await pb.collection("certificados").getOne(certificadoId)
      } else {
        vista = await pb.send(`/api/certificados/vista/${encodeURIComponent(actaId)}`, { method: "GET" })
        firmante = parroquia?.parroco ?? ""
      }
      await cargarAnteriores()
    } catch (err) {
      error = mensajeError(err)
    } finally {
      cargando = false
    }
  })

  function recordar() {
    recordarImpresion({ papel, conImagenes })
  }

  // Espera a que carguen membrete, sello y firma para que salgan en el papel.
  async function imprimir() {
    await tick()
    const imagenes = [...(hoja?.querySelectorAll("img") ?? [])]
    await Promise.all(imagenes.map((i) => i.complete ? null : new Promise((listo) => { i.onload = i.onerror = listo })))
    window.print()
  }

  async function emitir() {
    error = ""
    emitiendo = true
    try {
      emitido = await pb.collection("certificados").create({
        acta: actaId, fines, firmante, cargo, fecha_expedicion: fecha,
      })
      await cargarAnteriores()
      await imprimir()
    } catch (err) {
      error = mensajeError(err)
    } finally {
      emitiendo = false
    }
  }

  function otro() {
    if (vista) {
      emitido = null
    } else {
      window.location.hash = `/certificado/${actaId}`
    }
  }

  const persona = (c) => c.expand?.emitido_por?.name || c.expand?.emitido_por?.email || "—"
</script>

<svelte:head>{@html paginaCSS}</svelte:head>

<div class="no-imprimir">
  <a href="#/certificados">← Certificados emitidos</a>
  <h1>Fe de bautismo</h1>
</div>

{#if error}<div class="alerta error no-imprimir" role="alert">{error}</div>{/if}

{#if cargando}
  <p class="suave">Cargando…</p>
{:else if datos}
  <section class="tarjeta opciones no-imprimir" aria-label="Opciones del certificado">
    {#if enBorrador}
      <div class="alerta error">
        Esta acta todavía no fue revisada. Antes de emitir la fe de bautismo, compare los datos con el libro y márquela como revisada.
        {#if vista.pagina}<a href="#/pagina/{vista.pagina}/{vista.posicion || 1}">Abrir el acta</a>{/if}
      </div>
    {/if}

    {#if emitido}
      <p class="emitido">
        Certificado <strong>N.º {emitido.numero}</strong> emitido.
        {#if certificadoId}Es una copia: se imprime igual que la primera vez.{/if}
      </p>
      <div class="fila">
        <button class="boton" onclick={imprimir}>Imprimir</button>
        <button class="boton secundario" onclick={otro}>Emitir otro certificado de esta acta</button>
      </div>
    {:else}
      <div class="rejilla">
        <div class="campo">
          <label for="cert-fines">Para fines de</label>
          <input id="cert-fines" list="lista-fines" bind:value={fines} maxlength="200" placeholder="Opcional" />
        </div>
        <div class="campo">
          <label for="cert-firmante">Firma</label>
          <input id="cert-firmante" bind:value={firmante} maxlength="200" required />
        </div>
        <div class="campo">
          <label for="cert-cargo">Cargo</label>
          <input id="cert-cargo" list="lista-cargos" bind:value={cargo} maxlength="120" />
        </div>
        <div class="campo">
          <label for="cert-fecha">Fecha de expedición</label>
          <input id="cert-fecha" type="date" bind:value={fecha} required />
        </div>
      </div>
      <datalist id="lista-fines">{#each FINES as f}<option value={f}></option>{/each}</datalist>
      <datalist id="lista-cargos">{#each CARGOS as c}<option value={c}></option>{/each}</datalist>
    {/if}

    <div class="fila impresion">
      <div class="campo">
        <label for="cert-papel">Papel</label>
        <select id="cert-papel" bind:value={papel} onchange={recordar}>
          {#each Object.entries(PAPELES) as [valor, pp]}<option value={valor}>{pp.texto}</option>{/each}
        </select>
      </div>
      <label class="casilla">
        <input type="checkbox" bind:checked={conImagenes} onchange={recordar} />
        Imprimir el sello y la firma escaneados
      </label>
    </div>

    {#if !emitido}
      <div class="fila">
        <button class="boton" onclick={emitir} disabled={emitiendo || enBorrador || !firmante.trim() || !fecha}>
          {emitiendo ? "Emitiendo…" : "Emitir e imprimir"}
        </button>
        <span class="suave">Al emitir se le asigna un número y queda registrado.</span>
      </div>
    {/if}
    <p class="ayuda">En la ventana de impresión, desmarque «Encabezados y pies de página» y deje la escala al 100 %.</p>
    {#if parroquia && !parroquia.membrete}
      <p class="ayuda">Sin imagen de membrete se usan los datos de la parroquia. Puede cargarla en <a href="#/parroquia">Parroquia</a>.</p>
    {/if}
  </section>

  <div class="vista">
    <article bind:this={hoja} class="hoja" class:sin-emitir={!emitido}
             style="--ancho: {medidas.ancho}mm; --alto: {medidas.alto}mm" aria-label="Vista previa del certificado">
      <header class="membrete">
        {#if imagen.membrete}
          <img src={imagen.membrete} alt="Membrete de {p.nombre}" />
        {:else}
          <div class="nombre-parroquia">{p.nombre}</div>
          {#if p.diocesis}<div>{p.diocesis}</div>{/if}
          <div class="contacto">{[p.direccion, p.ciudad, p.telefono && `Tel. ${p.telefono}`].filter(Boolean).join(" · ")}</div>
        {/if}
      </header>

      <div class="numero">Certificado N.º {emitido?.numero ?? "—"}</div>
      <h2 class="titulo">Fe de Bautismo</h2>

      <p>
        Quien suscribe, {impreso.cargo || "Párroco"} de {laParroquia(p.nombre)}{p.diocesis ? `, ${p.diocesis}` : ""},
        certifica que en el libro de bautismos {referenciaLibro(datos.libro)}{datos.folio ? `, folio ${datos.folio}` : ""}{datos.numero_acta ? `, número ${datos.numero_acta}` : ""},
        se encuentra asentada la partida de bautismo de:
      </p>

      <p class="bautizado">{nombreCompleto(datos)}</p>

      <dl class="datos">
        {#each filas as [etiqueta, valor]}
          <dt>{etiqueta}:</dt><dd>{valor}</dd>
        {/each}
      </dl>

      <p class="notas"><strong>Notas marginales:</strong> {datos.notas_marginales || "No tiene."}</p>

      <p>
        Es fiel copia del original. Se expide a solicitud de la parte interesada{paraFines(impreso.fines)}{p.ciudad ? `, en ${p.ciudad}` : ""},
        {expedicionEnLetras(impreso.fecha)}.
      </p>

      <footer class="pie">
        <div class="sello">
          {#if imagen.sello}<img src={imagen.sello} alt="Sello parroquial" />{:else}<span>L. S.</span>{/if}
        </div>
        <div class="firma">
          {#if imagen.firma}<img src={imagen.firma} alt="Firma" />{/if}
          <div class="linea-firma"></div>
          <div>{impreso.firmante}</div>
          <div class="cargo">{impreso.cargo}</div>
        </div>
      </footer>
      <div class="referencia">
        {etiquetaLibro(datos.libro)}{datos.folio ? ` · Folio ${datos.folio}` : ""}{datos.numero_acta ? ` · Acta N.º ${datos.numero_acta}` : ""}
        {#if emitido} · Certificado N.º {emitido.numero}{/if}
      </div>
    </article>
  </div>

  {#if anteriores.length}
    <section class="no-imprimir anteriores">
      <h2>Certificados emitidos de esta acta</h2>
      <div class="tabla-contenedor">
        <table class="tabla">
          <thead><tr><th>N.º</th><th>Expedido</th><th>Para fines de</th><th>Emitido por</th><th></th></tr></thead>
          <tbody>
            {#each anteriores as c (c.id)}
              <tr class:actual={c.id === emitido?.id}>
                <td>{c.numero}</td>
                <td>{formatearFecha(c.fecha_expedicion)}</td>
                <td>{c.fines || "—"}</td>
                <td>{persona(c)}</td>
                <td>{#if c.id !== emitido?.id}<a href="#/certificado/{actaId}/{c.id}">Reimprimir</a>{/if}</td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    </section>
  {/if}
{/if}

<style>
  .opciones { margin-bottom: 1.25rem; }
  .rejilla { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); column-gap: 1rem; }
  .impresion { align-items: flex-end; }
  .impresion .campo { flex: 0 1 260px; }
  .casilla { display: flex; gap: 0.5rem; align-items: center; margin-bottom: 1rem; }
  .casilla input { width: 20px; height: 20px; }
  .emitido { font-size: 1.1rem; margin: 0 0 0.75rem; }
  .emitido + .fila { margin-bottom: 1.25rem; }
  .ayuda { font-size: 0.9rem; color: var(--texto-suave); margin: 0.75rem 0 0; }
  .alerta a { color: inherit; font-weight: 600; margin-left: 0.25rem; }
  .anteriores { margin-top: 1.5rem; }
  tr.actual td { font-weight: 600; }
  .anteriores td:nth-child(-n + 2) { white-space: nowrap; }

  /* La hoja se ve en pantalla a su tamaño real; en angosto se desplaza dentro de su caja. */
  .vista { overflow-x: auto; padding: 1rem; background: #e7e3da; border-radius: var(--radio); }
  .hoja {
    width: var(--ancho); min-height: var(--alto); margin: 0 auto; padding: 15mm 20mm;
    background: #fff; color: #111; box-shadow: 0 2px 12px rgb(0 0 0 / 0.15);
    display: flex; flex-direction: column;
    font-family: Georgia, "Times New Roman", serif; font-size: 12pt; line-height: 1.55;
  }
  .hoja p { margin: 0 0 0.9em; text-align: justify; }
  .membrete { text-align: center; padding-bottom: 4mm; border-bottom: 3px double var(--primario); margin-bottom: 4mm; }
  .membrete img { max-width: 100%; max-height: 38mm; object-fit: contain; }
  .nombre-parroquia { font-size: 16pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
  .contacto { font-size: 10pt; }
  .numero { text-align: right; font-size: 10pt; }
  .titulo {
    font-family: inherit; text-align: center; font-size: 22pt; font-variant: small-caps;
    letter-spacing: 0.08em; margin: 3mm 0 7mm; color: #111;
  }
  .bautizado { text-align: center !important; font-size: 16pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; margin: 2mm 0 5mm !important; }
  .datos { display: grid; grid-template-columns: max-content 1fr; gap: 1.5mm 5mm; margin: 0 0 5mm 8mm; }
  .datos dt { font-weight: 700; }
  .datos dd { margin: 0; }
  .pie { margin-top: auto; padding-top: 14mm; display: flex; justify-content: center; position: relative; }
  .firma { width: 75mm; text-align: center; position: relative; z-index: 1; }
  .firma img { display: block; max-width: 60mm; max-height: 24mm; margin: 0 auto -6mm; object-fit: contain; mix-blend-mode: multiply; }
  .linea-firma { border-top: 1px solid #111; margin: 18mm 0 1.5mm; }
  .firma img + .linea-firma { margin-top: 0; }
  .cargo { font-style: italic; }
  .sello {
    position: absolute; left: calc(50% - 82mm); bottom: 2mm; width: 38mm; height: 38mm;
    display: grid; place-items: center;
  }
  .sello img { max-width: 100%; max-height: 100%; object-fit: contain; transform: rotate(-8deg); mix-blend-mode: multiply; opacity: 0.9; }
  .sello span { font-size: 10pt; color: #777; }
  .referencia { text-align: center; font-size: 9pt; color: #555; margin-top: 6mm; }

  @media print {
    :global(.lateral), .no-imprimir { display: none !important; }
    :global(html), :global(body) { background: #fff !important; }
    :global(.marco) { display: block !important; }
    :global(.contenido) { padding: 0 !important; max-width: none !important; }
    .vista { padding: 0; background: none; overflow: visible; }
    /* El margen lo pone @page; la hoja ocupa el área útil para que la firma quede abajo. */
    .hoja { width: auto; min-height: calc(var(--alto) - 31mm); padding: 0; box-shadow: none; margin: 0; }
    .hoja.sin-emitir::before {
      content: "VISTA PREVIA — SIN VALIDEZ"; position: fixed; top: 45%; left: 0; right: 0; text-align: center;
      font-size: 30pt; color: rgb(160 0 0 / 0.35); transform: rotate(-30deg);
    }
  }
</style>
