// Reglas de actas y páginas compartidas por los hooks de actas.pb.js.

const MAX_SEGMENTOS = 6

const esRevisada = (estado) => estado === "revisada" || estado === "verificada"

// Los campos JSON llegan como bytes crudos (JSONRaw), no como objetos JS.
function leerJSON(valor) {
  if (valor === null || valor === undefined) return null
  const texto = typeof valor === "string" ? valor : toString(valor)
  if (!texto || texto === "null") return null
  return JSON.parse(texto)
}

// pendiente: sin recuadros · segmentada: con recuadros sin transcribir del todo ·
// completa: todas las actas de la página transcritas.
function estadoPagina(app, pagina) {
  const segmentos = leerJSON(pagina.get("segmentos"))
  const cantidad = Array.isArray(segmentos) ? segmentos.length : 0
  const actas = app.countRecords("actas_bautismo", $dbx.hashExp({ pagina: pagina.id }))
  if (actas > 0 && actas >= cantidad) return "completa"
  if (cantidad > 0) return "segmentada"
  return "pendiente"
}

function recalcularPagina(app, paginaId) {
  if (!paginaId) return
  let pagina
  try {
    pagina = app.findRecordById("paginas", paginaId)
  } catch {
    return // la página fue borrada
  }
  const nuevo = estadoPagina(app, pagina)
  if (pagina.getString("estado") !== nuevo) {
    pagina.set("estado", nuevo)
    app.save(pagina)
  }
}

// Los recuadros deben ser proporciones dentro de la imagen.
function validarSegmentos(valor) {
  const lista = leerJSON(valor)
  if (lista === null) return
  if (!Array.isArray(lista) || lista.length > MAX_SEGMENTOS) {
    throw new BadRequestError(`Se permiten hasta ${MAX_SEGMENTOS} recuadros por página.`)
  }
  for (const s of lista) {
    const ok = ["x", "y", "ancho", "alto"].every((k) => typeof s?.[k] === "number" && s[k] >= 0 && s[k] <= 1)
    if (!ok || s.ancho <= 0 || s.alto <= 0 || s.x + s.ancho > 1.0001 || s.y + s.alto > 1.0001) {
      throw new BadRequestError("Recuadro inválido: debe quedar dentro de la imagen.")
    }
  }
}

module.exports = { esRevisada, estadoPagina, recalcularPagina, validarSegmentos }
