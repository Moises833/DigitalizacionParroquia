// Fe de bautismo: datos que se imprimen y numeración de los certificados.
// Se carga con require() dentro de cada handler (ver certificados.pb.js).

const CAMPOS_ACTA = [
  "nombres", "apellidos", "sexo", "fecha_nacimiento", "lugar_nacimiento", "fecha_bautizo",
  "padre", "madre", "padrino", "madrina", "ministro", "registro_civil", "notas_marginales", "numero_acta",
]
const CAMPOS_PARROQUIA = ["nombre", "diocesis", "direccion", "ciudad", "telefono", "parroco"]

// Lo que se imprime en el certificado. Es lo mismo para la vista previa y para
// la copia que se guarda al emitir, así lo impreso coincide con lo que se vio.
function datosActa(app, acta) {
  const datos = {}
  for (const campo of CAMPOS_ACTA) datos[campo] = acta.getString(campo)
  datos.libro = app.findRecordById("libros", acta.getString("libro")).getString("numero")
  const paginaId = acta.getString("pagina")
  datos.folio = paginaId ? app.findRecordById("paginas", paginaId).getString("folio") : ""

  const parroquia = app.findRecordsByFilter("parroquia", "", "created", 1, 0)[0]
  datos.parroquia = {}
  for (const campo of CAMPOS_PARROQUIA) datos.parroquia[campo] = parroquia ? parroquia.getString(campo) : ""
  return datos
}

function puedeCertificarse(acta) {
  return acta.getString("estado") !== "borrador"
}

function hoy() {
  const d = new Date()
  const dos = (n) => String(n).padStart(2, "0")
  return `${d.getFullYear()}-${dos(d.getMonth() + 1)}-${dos(d.getDate())}`
}

// Completa lo que decide el servidor; el cliente no puede elegir número, datos ni autor.
function preparar(app, certificado, usuarioId) {
  const acta = app.findRecordById("actas_bautismo", certificado.getString("acta"))
  if (!puedeCertificarse(acta)) {
    throw new BadRequestError("El acta todavía no fue revisada. Revísela antes de emitir la fe de bautismo.")
  }
  if (!acta.getString("nombres")) {
    throw new BadRequestError("El acta no tiene el nombre del bautizado.")
  }

  const anio = new Date().getFullYear()
  const fila = new DynamicModel({ ultimo: 0 })
  app.db().newQuery("SELECT COALESCE(MAX(correlativo), 0) AS ultimo FROM certificados WHERE anio = {:anio}")
    .bind({ anio })
    .one(fila)
  const correlativo = fila.ultimo + 1

  certificado.set("anio", anio)
  certificado.set("correlativo", correlativo)
  certificado.set("numero", `${anio}-${String(correlativo).padStart(4, "0")}`)
  certificado.set("datos", datosActa(app, acta))
  certificado.set("emitido_por", usuarioId)
  if (!certificado.getString("fecha_expedicion")) certificado.set("fecha_expedicion", hoy())
  if (!certificado.getString("cargo")) certificado.set("cargo", "Párroco")
  for (const campo of ["fines", "firmante", "cargo"]) {
    certificado.set(campo, certificado.getString(campo).trim())
  }
}

module.exports = { datosActa, puedeCertificarse, preparar }
