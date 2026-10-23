/// <reference path="../pb_data/types.d.ts" />

// Nota: cada handler corre en su propia VM y no ve las constantes de este
// archivo; lo compartido está en actas.js y se carga con require().

// --- Coherencia del acta con su página --------------------------------------

// Si el acta pertenece a una página, el libro siempre es el de esa página.
onRecordValidate((e) => {
  const paginaId = e.record.getString("pagina")
  if (paginaId) {
    const pagina = e.app.findRecordById("paginas", paginaId)
    e.record.set("libro", pagina.getString("libro"))
  }
  e.next()
}, "actas_bautismo")

// --- Quién creó y quién revisó (no lo decide el cliente) --------------------

onRecordCreateRequest((e) => {
  const { esRevisada } = require(`${__hooks}/actas.js`)
  e.record.set("creado_por", e.auth?.id ?? "")
  e.record.set("revisado_por", esRevisada(e.record.getString("estado")) ? (e.auth?.id ?? "") : "")
  e.next()
}, "actas_bautismo")

onRecordUpdateRequest((e) => {
  const { esRevisada } = require(`${__hooks}/actas.js`)
  const original = e.record.original()
  e.record.set("creado_por", original.getString("creado_por"))
  const estado = e.record.getString("estado")
  if (!esRevisada(estado)) {
    e.record.set("revisado_por", "")
  } else if (estado !== original.getString("estado") || !original.getString("revisado_por")) {
    e.record.set("revisado_por", e.auth?.id ?? "")
  } else {
    e.record.set("revisado_por", original.getString("revisado_por"))
  }
  e.next()
}, "actas_bautismo")

// --- Estado de la página según sus recuadros y actas ------------------------

onRecordAfterCreateSuccess((e) => {
  require(`${__hooks}/actas.js`).recalcularPagina(e.app, e.record.getString("pagina"))
  e.next()
}, "actas_bautismo")

onRecordAfterUpdateSuccess((e) => {
  const actas = require(`${__hooks}/actas.js`)
  const antes = e.record.original().getString("pagina")
  const ahora = e.record.getString("pagina")
  actas.recalcularPagina(e.app, ahora)
  if (antes !== ahora) actas.recalcularPagina(e.app, antes)
  e.next()
}, "actas_bautismo")

onRecordAfterDeleteSuccess((e) => {
  require(`${__hooks}/actas.js`).recalcularPagina(e.app, e.record.getString("pagina"))
  e.next()
}, "actas_bautismo")

onRecordUpdate((e) => {
  const actas = require(`${__hooks}/actas.js`)
  actas.validarSegmentos(e.record.get("segmentos"))
  e.record.set("estado", actas.estadoPagina(e.app, e.record))
  e.next()
}, "paginas")

// --- Sugerencias para autocompletar -----------------------------------------

routerAdd("GET", "/api/sugerencias/{campo}", (e) => {
  const campo = e.request.pathValue("campo")
  // Lista cerrada: el nombre del campo se interpola en el SQL.
  if (!["ministro", "lugar_nacimiento"].includes(campo)) {
    throw new BadRequestError("Campo no disponible para sugerencias.")
  }
  const filas = arrayOf(new DynamicModel({ valor: "", n: 0 }))
  e.app.db().newQuery(
    `SELECT ${campo} AS valor, COUNT(*) AS n FROM actas_bautismo
     WHERE ${campo} != '' GROUP BY ${campo} ORDER BY n DESC, valor LIMIT 300`,
  ).all(filas)
  return e.json(200, filas.map((f) => f.valor))
}, $apis.requireAuth("users"))
