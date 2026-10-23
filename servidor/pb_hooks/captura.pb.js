/// <reference path="../pb_data/types.d.ts" />

// --- Desde la PC (requiere sesión iniciada) ---------------------------------

// Abre una vinculación para un libro y devuelve el token para el QR.
routerAdd("POST", "/api/captura/sesiones", (e) => {
  const captura = require(`${__hooks}/captura.js`)
  const datos = new DynamicModel({ libro: "", folio_siguiente: 0 })
  e.bindBody(datos)
  if (!datos.libro) throw new BadRequestError("Seleccione un libro.")

  let nueva
  try {
    nueva = captura.nuevaSesion(e.app, e.auth.id, datos.libro, datos.folio_siguiente)
  } catch (err) {
    if (String(err).includes("no rows")) throw new NotFoundError("El libro no existe.")
    throw err
  }

  const puerto = (e.request.host.split(":")[1]) || "8090"
  return e.json(201, {
    ...captura.resumenSesion(e.app, nueva.sesion),
    token: nueva.token,
    puerto,
    direcciones: captura.direccionesLocales(),
  })
}, $apis.requireAuth("users"))

// Cambia el próximo folio o cierra la vinculación.
routerAdd("PATCH", "/api/captura/sesiones/{id}", (e) => {
  const captura = require(`${__hooks}/captura.js`)
  let sesion
  try {
    sesion = e.app.findRecordById("sesiones_captura", e.request.pathValue("id"))
  } catch {
    throw new NotFoundError("La vinculación no existe.")
  }
  if (sesion.getString("creado_por") !== e.auth.id && e.auth.getString("rol") !== "admin") {
    throw new ForbiddenError("Esta vinculación pertenece a otro usuario.")
  }

  const datos = new DynamicModel({ folio_siguiente: -1, activa: true })
  e.bindBody(datos)
  if (datos.folio_siguiente >= 1) sesion.set("folio_siguiente", datos.folio_siguiente)
  if (datos.activa === false) sesion.set("activa", false)
  e.app.save(sesion)

  return e.json(200, captura.resumenSesion(e.app, sesion))
}, $apis.requireAuth("users"))

// --- Desde el teléfono (autorizado solo por el token del QR) ----------------

routerAdd("GET", "/api/captura/movil/{token}", (e) => {
  const captura = require(`${__hooks}/captura.js`)
  const sesion = captura.sesionPorToken(e.app, e.request.pathValue("token"))
  return e.json(200, captura.resumenSesion(e.app, sesion))
})

routerAdd("POST", "/api/captura/movil/{token}/paginas", (e) => {
  const captura = require(`${__hooks}/captura.js`)
  // findUploadedFiles lanza un error si el campo no viene en el formulario.
  const archivosDe = (campo) => {
    try { return e.findUploadedFiles(campo) || [] } catch { return [] }
  }
  const archivos = archivosDe("imagen")
  if (archivos.length !== 1) {
    throw new BadRequestError("Envíe una sola imagen en el campo «imagen».")
  }
  const miniatura = archivosDe("miniatura")[0] // opcional
  const resultado = captura.guardarFoto(e.app, e.request.pathValue("token"), archivos[0], miniatura)
  return e.json(201, resultado)
}, $apis.bodyLimit(16 * 1024 * 1024))
