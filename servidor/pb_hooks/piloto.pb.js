/// <reference path="../pb_data/types.d.ts" />

// Herramientas del piloto: observaciones, informe y exportación (ver piloto.js).

// --- Observaciones: el autor y el estado inicial los pone el servidor ---------

onRecordCreateRequest((e) => {
  e.record.set("usuario", e.auth?.id ?? "")
  e.record.set("estado", "pendiente")
  e.record.set("respuesta", "")
  e.record.set("texto", e.record.getString("texto").trim())
  e.next()
}, "observaciones")

// El administrador solo cambia el estado y la respuesta.
onRecordUpdateRequest((e) => {
  const original = e.record.original()
  for (const campo of ["usuario", "tipo", "texto", "pantalla"]) e.record.set(campo, original.get(campo))
  e.next()
}, "observaciones")

// --- Informe ------------------------------------------------------------------

routerAdd("GET", "/api/piloto/informe", (e) => {
  if (e.auth?.getString("rol") !== "admin") throw new ForbiddenError("Solo el administrador.")
  return e.json(200, require(`${__hooks}/piloto.js`).informe(e.app))
}, $apis.requireAuth("users"))

// --- Exportar las actas de un libro -------------------------------------------

routerAdd("GET", "/api/exportar/actas/{libro}", (e) => {
  if (e.auth?.getString("rol") !== "admin") throw new ForbiddenError("Solo el administrador.")
  let r
  try {
    r = require(`${__hooks}/piloto.js`).csvActas(e.app, e.request.pathValue("libro"))
  } catch (err) {
    if (String(err).includes("no rows")) throw new NotFoundError("No se encontró el libro.")
    throw err
  }
  const archivo = `actas ${r.nombre}`.replace(/[\\/:*?"<>|\r\n]+/g, "_").slice(0, 80)
  e.response.header().set("Content-Disposition",
    `attachment; filename="actas.csv"; filename*=UTF-8''${encodeURIComponent(archivo)}.csv`)
  e.response.header().set("X-Total-Actas", String(r.total))
  return e.blob(200, "text/csv; charset=utf-8", r.csv)
}, $apis.requireAuth("users"))
