// Lógica de las sesiones de captura (vinculación del teléfono).
// Se carga con require() dentro de cada handler de captura.pb.js.

const DURACION_HORAS = 8
const VIRTUAL = /vpn|radmin|hamachi|virtual|vbox|vmware|vethernet|hyper-v|wsl|tun|tap|zerotier|tailscale|loopback/i

// IPs IPv4 del equipo, primero las de Ethernet/Wi-Fi y al final las que parecen virtuales.
function direccionesLocales() {
  let salida = ""
  try {
    const comando = $os.cmd(
      "powershell", "-NoProfile", "-Command",
      "Get-NetIPAddress -AddressFamily IPv4 -AddressState Preferred | " +
      "Where-Object { $_.IPAddress -notlike '127.*' -and $_.IPAddress -notlike '169.254.*' } | " +
      "Select-Object IPAddress,InterfaceAlias | ConvertTo-Json -Compress",
    )
    salida = toString(comando.output())
  } catch (err) {
    $app.logger().warn("No se pudieron obtener las IPs locales", "error", String(err))
    return []
  }
  if (!salida.trim()) return []
  let lista = JSON.parse(salida)
  if (!Array.isArray(lista)) lista = [lista]

  return lista
    .map((d) => ({ ip: d.IPAddress, interfaz: d.InterfaceAlias, virtual: VIRTUAL.test(d.InterfaceAlias || "") }))
    .sort((a, b) => Number(a.virtual) - Number(b.virtual))
}

// Siguiente número de folio libre de un libro (máximo numérico + 1).
function siguienteFolio(app, libroId) {
  const filas = arrayOf(new DynamicModel({ folio: "" }))
  app.db().select("folio").from("paginas").where($dbx.hashExp({ libro: libroId })).all(filas)
  let maximo = 0
  for (const fila of filas) {
    const n = parseInt(fila.folio, 10)
    if (!isNaN(n) && n > maximo) maximo = n
  }
  return maximo + 1
}

function existeFolio(app, libroId, folio) {
  try {
    app.findFirstRecordByFilter("paginas", "libro = {:libro} && folio = {:folio}", { libro: libroId, folio: String(folio) })
    return true
  } catch {
    return false
  }
}

// Sesión activa y vigente para un token, o error 404.
function sesionPorToken(app, token) {
  try {
    return app.findFirstRecordByFilter(
      "sesiones_captura",
      "token_hash = {:hash} && activa = true && expira > @now",
      { hash: $security.sha256(String(token || "")) },
    )
  } catch {
    throw new NotFoundError("La vinculación no es válida o ya venció. Escanee de nuevo el código QR.")
  }
}

function resumenSesion(app, sesion) {
  const libro = app.findRecordById("libros", sesion.getString("libro"))
  return {
    id: sesion.id,
    libro: { id: libro.id, numero: libro.getString("numero") },
    folio_siguiente: sesion.getInt("folio_siguiente"),
    fotos_recibidas: sesion.getInt("fotos_recibidas"),
    expira: sesion.getString("expira"),
  }
}

function nuevaSesion(app, usuarioId, libroId, folioInicial) {
  const libro = app.findRecordById("libros", libroId)
  const token = $security.randomString(40)

  // Un usuario tiene como máximo una vinculación activa.
  const anteriores = app.findRecordsByFilter(
    "sesiones_captura", "creado_por = {:u} && activa = true", "", 0, 0, { u: usuarioId },
  )
  for (const s of anteriores) {
    s.set("activa", false)
    app.save(s)
  }

  const sesion = new Record(app.findCollectionByNameOrId("sesiones_captura"))
  sesion.set("libro", libro.id)
  sesion.set("token_hash", $security.sha256(token))
  sesion.set("folio_siguiente", folioInicial > 0 ? folioInicial : siguienteFolio(app, libro.id))
  sesion.set("expira", new Date(Date.now() + DURACION_HORAS * 3600 * 1000).toISOString())
  sesion.set("activa", true)
  sesion.set("fotos_recibidas", 0)
  sesion.set("creado_por", usuarioId)
  app.save(sesion)

  return { token, sesion }
}

// Guarda una foto recibida del teléfono como nueva página, con el siguiente folio libre.
function guardarFoto(app, token, archivo, miniatura) {
  let resultado = null
  app.runInTransaction((tx) => {
    const sesion = sesionPorToken(tx, token)
    const libroId = sesion.getString("libro")

    let folio = Math.max(1, sesion.getInt("folio_siguiente"))
    while (existeFolio(tx, libroId, folio)) folio++

    const pagina = new Record(tx.findCollectionByNameOrId("paginas"))
    pagina.set("libro", libroId)
    pagina.set("folio", String(folio))
    pagina.set("imagen", archivo)
    if (miniatura) pagina.set("miniatura", miniatura)
    pagina.set("estado", "pendiente")
    pagina.set("origen", "movil")
    tx.save(pagina)

    sesion.set("folio_siguiente", folio + 1)
    sesion.set("fotos_recibidas", sesion.getInt("fotos_recibidas") + 1)
    tx.save(sesion)

    require(`${__hooks}/util.js`).registrarAuditoria(
      tx, "crear", sesion.getString("creado_por"), pagina, null, pagina.publicExport(),
    )
    resultado = { id: pagina.id, folio: String(folio), folio_siguiente: folio + 1 }
  })
  return resultado
}

module.exports = { direccionesLocales, siguienteFolio, sesionPorToken, resumenSesion, nuevaSesion, guardarFoto }
