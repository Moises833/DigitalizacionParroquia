/// <reference path="../pb_data/types.d.ts" />

const AUDITADAS = ["libros", "paginas", "actas_bautismo", "parroquia", "users"]

// --- Caché de la interfaz ---------------------------------------------------
// Los archivos de /assets llevan un hash en el nombre y nunca cambian; el resto
// (index.html) debe revalidarse para que una actualización se vea al instante.

routerUse((e) => {
  const ruta = e.request.url.path
  if (ruta.startsWith("/assets/")) {
    e.response.header().set("Cache-Control", "public, max-age=31536000, immutable")
  } else if (!ruta.startsWith("/api/") && !ruta.startsWith("/_/")) {
    e.response.header().set("Cache-Control", "no-cache")
  }
  return e.next()
})

// --- Texto de búsqueda sin acentos ------------------------------------------

onRecordCreate((e) => {
  e.record.set("busqueda", require(`${__hooks}/util.js`).textoBusqueda(e.record))
  e.next()
}, "actas_bautismo")

onRecordUpdate((e) => {
  e.record.set("busqueda", require(`${__hooks}/util.js`).textoBusqueda(e.record))
  e.next()
}, "actas_bautismo")

// --- Auditoría de cambios hechos por usuarios -------------------------------

onRecordCreateRequest((e) => {
  e.next()
  const util = require(`${__hooks}/util.js`)
  util.registrarAuditoria(e.app, "crear", e.auth?.id, e.record, null, e.record.publicExport())
}, ...AUDITADAS)

onRecordUpdateRequest((e) => {
  const util = require(`${__hooks}/util.js`)
  const antes = JSON.parse(JSON.stringify(e.record.original().publicExport()))
  e.next()
  const despues = JSON.parse(JSON.stringify(e.record.publicExport()))
  const [cambiosAntes, cambiosDespues] = util.diferencias(antes, despues)
  if (Object.keys(cambiosDespues).length > 0) {
    util.registrarAuditoria(e.app, "editar", e.auth?.id, e.record, cambiosAntes, cambiosDespues)
  }
}, ...AUDITADAS)

onRecordDeleteRequest((e) => {
  const util = require(`${__hooks}/util.js`)
  const antes = e.record.publicExport()
  e.next()
  util.registrarAuditoria(e.app, "eliminar", e.auth?.id, e.record, antes, null)
}, ...AUDITADAS)

// --- Configuración inicial: crear el primer administrador -------------------

routerAdd("GET", "/api/configuracion-inicial", (e) => {
  const total = e.app.countRecords("users")
  return e.json(200, { pendiente: total === 0 })
})

routerAdd("POST", "/api/configuracion-inicial", (e) => {
  const datos = new DynamicModel({ nombre: "", email: "", password: "", parroquia: "" })
  e.bindBody(datos)

  if (!datos.nombre || !datos.email || !datos.parroquia) {
    throw new BadRequestError("Faltan datos obligatorios.")
  }
  if (String(datos.password).length < 8) {
    throw new BadRequestError("La contraseña debe tener al menos 8 caracteres.")
  }

  e.app.runInTransaction((txApp) => {
    // Se comprueba dentro de la transacción para que nadie más pueda
    // adelantarse y crear otro administrador al mismo tiempo.
    if (txApp.countRecords("users") > 0) {
      throw new ForbiddenError("El sistema ya fue configurado.")
    }

    const admin = new Record(txApp.findCollectionByNameOrId("users"))
    admin.set("name", datos.nombre)
    admin.set("email", datos.email)
    admin.setPassword(datos.password)
    admin.set("rol", "admin")
    admin.set("verified", true)
    txApp.save(admin)

    const parroquia = txApp.findRecordsByFilter("parroquia", "", "created", 1, 0)[0]
    parroquia.set("nombre", datos.parroquia)
    txApp.save(parroquia)
  })

  return e.json(201, { ok: true })
})
