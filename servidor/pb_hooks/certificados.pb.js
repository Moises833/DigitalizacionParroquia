/// <reference path="../pb_data/types.d.ts" />

// Nota: cada handler corre en su propia VM; lo compartido está en certificados.js.

// --- Emitir: número, copia de los datos y autor los pone el servidor ---------

onRecordCreateRequest((e) => {
  require(`${__hooks}/certificados.js`).preparar(e.app, e.record, e.auth?.id ?? "")
  e.next()
}, "certificados")

// PocketBase ya lo impide por la relación obligatoria; esto da un mensaje claro.
onRecordDelete((e) => {
  const total = e.app.countRecords("certificados", $dbx.hashExp({ acta: e.record.id }))
  if (total > 0) {
    throw new BadRequestError(`Se emitieron ${total} certificado(s) de esta acta y no se puede eliminar.`)
  }
  e.next()
}, "actas_bautismo")

// --- Vista previa: los mismos datos que se guardarán al emitir ---------------

routerAdd("GET", "/api/certificados/vista/{acta}", (e) => {
  const certificados = require(`${__hooks}/certificados.js`)
  let acta
  try {
    acta = e.app.findRecordById("actas_bautismo", e.request.pathValue("acta"))
  } catch {
    throw new NotFoundError("No se encontró el acta.")
  }
  return e.json(200, {
    datos: certificados.datosActa(e.app, acta),
    estado: acta.getString("estado"),
    pagina: acta.getString("pagina"),
    posicion: acta.getInt("posicion"),
  })
}, $apis.requireAuth("users"))
