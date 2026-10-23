// Utilidades compartidas por los hooks.
// Cada hook corre en su propia VM, así que se cargan con require() dentro del handler.

const CAMPOS_BUSQUEDA = [
  "nombres", "apellidos", "padre", "madre", "padrino", "madrina",
  "ministro", "lugar_nacimiento", "numero_acta", "registro_civil",
]

// "Núñez Ángel" -> "nunez angel"
function normalizar(texto) {
  return String(texto || "")
    .normalize("NFD")
    .replace(/[̀-ͯ]/g, "")
    .toLowerCase()
    .replace(/\s+/g, " ")
    .trim()
}

function textoBusqueda(record) {
  return CAMPOS_BUSQUEDA.map((campo) => normalizar(record.getString(campo)))
    .filter(Boolean)
    .join(" | ")
}

function registrarAuditoria(app, accion, usuarioId, record, antes, despues) {
  try {
    const coleccion = app.findCollectionByNameOrId("auditoria")
    const registro = new Record(coleccion)
    registro.set("usuario", usuarioId || "")
    registro.set("accion", accion)
    registro.set("coleccion", record.collection().name)
    registro.set("registro", record.id)
    registro.set("antes", antes)
    registro.set("despues", despues)
    app.save(registro)
  } catch (err) {
    // Un fallo de auditoría nunca debe impedir guardar el acta.
    app.logger().error("No se pudo registrar la auditoría", "error", String(err))
  }
}

// Devuelve solo los campos que cambiaron entre dos exportaciones.
function diferencias(antes, despues) {
  const cambiosAntes = {}
  const cambiosDespues = {}
  for (const clave of Object.keys(despues)) {
    if (clave === "updated") continue
    if (JSON.stringify(antes[clave]) !== JSON.stringify(despues[clave])) {
      cambiosAntes[clave] = antes[clave]
      cambiosDespues[clave] = despues[clave]
    }
  }
  return [cambiosAntes, cambiosDespues]
}

// Campos normalizados para filtrar por persona sin importar acentos ni mayúsculas:
// "busqueda" (todo), "b_nombre" (bautizado), "b_padres" y "b_padrinos".
function asignarBusqueda(record) {
  const unir = (...campos) => campos.map((c) => normalizar(record.getString(c))).filter(Boolean).join(" | ")
  record.set("busqueda", textoBusqueda(record))
  record.set("b_nombre", unir("nombres", "apellidos"))
  record.set("b_padres", unir("padre", "madre"))
  record.set("b_padrinos", unir("padrino", "madrina"))
}

module.exports = { normalizar, textoBusqueda, asignarBusqueda, registrarAuditoria, diferencias }
