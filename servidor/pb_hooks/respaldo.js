// Respaldos incrementales. Se carga con require() dentro de cada handler.
//
// Cada destino (una carpeta o un bucket S3) guarda:
//   db/datos_AAAA-MM-DD_HH-MM-SS.db   copia completa de la base (las últimas N)
//   storage/<colección>/<registro>/<archivo>   espejo de las imágenes
// Las imágenes nunca cambian (un archivo nuevo tiene otro nombre), así que cada
// respaldo solo copia las nuevas: el primero tarda, los siguientes son rápidos.
// En el destino no se borra ninguna imagen, aunque se borre en el sistema.
// La copia de la base se sube al final, cuando ya están todas sus imágenes: si
// se corta a mitad, la copia anterior sigue completa y la próxima continúa.

const DESTINOS = ["carpeta", "nube"]
const ESPERA_TRAS_ERROR = 15 * 60 * 1000
const BLOQUEO_VENCE = 10 * 60 * 1000
const HISTORIAL_MAXIMO = 300

function config(app) {
  return app.findRecordsByFilter("config_respaldo", "", "", 1, 0)[0]
}

function absoluta(ruta) {
  return $filepath.isAbs(ruta) ? ruta : $filepath.join($os.getwd(), ruta)
}

function carpetaDestino(app, cfg) {
  const ruta = cfg.getString("carpeta_ruta").trim()
  return ruta || $filepath.join($filepath.dir(absoluta(app.dataDir())), "respaldos")
}

function existe(ruta) {
  try {
    $os.stat(ruta)
    return true
  } catch {
    return false
  }
}

// Mensajes entendibles para los errores más comunes.
function explicar(err) {
  const texto = String(err?.message ?? err).replace(/^(GoError|Error):\s*/, "").slice(0, 600)
  const casos = [
    [/InvalidAccessKeyId|SignatureDoesNotMatch|AccessDenied|Unauthorized|status 40[13]|\b40[13]\b/i,
      "La nube rechazó el acceso: revise el Key ID, la clave secreta y que la clave tenga permiso sobre el bucket."],
    [/NoSuchBucket/i, "No existe el bucket indicado en la nube."],
    [/no such host|connection refused|actively refused|i\/o timeout|dial tcp|network is unreachable|deadline exceeded|EOF/i,
      "No hay conexión con la nube. Se reintentará más tarde."],
    [/no space left|not enough space|espacio/i, "No queda espacio en el disco de destino."],
  ]
  for (const [patron, mensaje] of casos) if (patron.test(texto)) return `${mensaje} (${texto})`
  return texto
}

function sinBarraFinal(texto) {
  return String(texto ?? "").trim().replace(/\/+$/, "")
}

// Abre el destino como sistema de archivos de PocketBase (local o S3).
function abrir(app, cfg, destino, datosNube) {
  if (destino === "carpeta") {
    const ruta = carpetaDestino(app, cfg)
    if (!existe(ruta)) {
      if (!existe($filepath.dir(ruta))) {
        throw new Error(`No se encontró «${ruta}». Si es un disco externo o un pendrive, revise que esté conectado.`)
      }
      $os.mkdirAll(ruta, 0o755)
    }
    return { fs: $filesystem.local(ruta), prefijo: "", descripcion: ruta }
  }
  const n = datosNube ?? {
    endpoint: cfg.getString("s3_endpoint"),
    region: cfg.getString("s3_region"),
    bucket: cfg.getString("s3_bucket"),
    prefijo: cfg.getString("s3_prefijo"),
    claveId: cfg.getString("s3_clave_id"),
    secreto: cfg.getString("s3_secreto"),
  }
  if (!n.endpoint || !n.bucket || !n.claveId || !n.secreto) {
    throw new Error("Falta completar los datos de la nube (endpoint, bucket, Key ID y clave secreta).")
  }
  let endpoint = sinBarraFinal(n.endpoint)
  if (!/^https?:\/\//i.test(endpoint)) endpoint = `https://${endpoint}`
  const fs = $filesystem.s3(n.bucket.trim(), n.region.trim() || "us-east-1", endpoint, n.claveId.trim(), n.secreto.trim(), true)
  const prefijo = sinBarraFinal(n.prefijo)
  return { fs, prefijo: prefijo ? `${prefijo}/` : "", descripcion: `${n.bucket}/${prefijo}` }
}

// Archivos de pb_data/storage, sin las miniaturas (se regeneran solas) ni los
// .attrs (metadatos que cada destino escribe por su cuenta).
function archivosLocales(app) {
  const lista = []
  const recorrer = (dir, relativa) => {
    let entradas
    try {
      entradas = $os.readDir(dir)
    } catch {
      return
    }
    for (const entrada of entradas) {
      const nombre = entrada.name()
      const ruta = $filepath.join(dir, nombre)
      const clave = relativa ? `${relativa}/${nombre}` : nombre
      if (entrada.isDir()) {
        if (!nombre.startsWith("thumbs_")) recorrer(ruta, clave)
      } else if (!nombre.endsWith(".attrs")) {
        lista.push({ ruta, clave, tam: entrada.info().size() })
      }
    }
  }
  recorrer($filepath.join(absoluta(app.dataDir()), "storage"), "")
  return lista
}

function marcaDeTiempo() {
  const d = new Date()
  const dos = (n) => String(n).padStart(2, "0")
  return `${d.getFullYear()}-${dos(d.getMonth() + 1)}-${dos(d.getDate())}_${dos(d.getHours())}-${dos(d.getMinutes())}-${dos(d.getSeconds())}`
}

// Copia coherente de la base aunque se esté usando (VACUUM INTO).
function instantanea(app) {
  const ruta = $filepath.join($os.tempDir(), `respaldo_${Date.now()}_${$security.randomString(6)}.db`)
  app.db().newQuery("VACUUM INTO {:ruta}").bind({ ruta }).execute()
  return ruta
}

function copiasDeLaBase(destino) {
  return destino.fs.list(`${destino.prefijo}db/`)
    .filter((o) => /\/datos_[\d_-]+\.db$/.test(o.key))
    .sort((a, b) => (a.key < b.key ? 1 : -1))
}

function respaldar(app, cfg, destino, limite) {
  const r = { destino, estado: "ok", copiados: 0, bytes: 0, total: 0, base: "", error: "" }
  let d = null
  let temporal = ""
  try {
    d = abrir(app, cfg, destino)
    temporal = instantanea(app)

    const enDestino = {}
    for (const o of d.fs.list(`${d.prefijo}storage/`)) enDestino[o.key] = o.size
    const locales = archivosLocales(app)
    r.total = locales.length
    for (const a of locales) {
      const clave = `${d.prefijo}storage/${a.clave}`
      if (enDestino[clave] === a.tam) continue
      if (Date.now() > limite) {
        r.estado = "parcial"
        break
      }
      d.fs.uploadFile($filesystem.fileFromPath(a.ruta), clave)
      r.copiados++
      r.bytes += a.tam
    }

    if (r.estado === "ok") {
      r.base = `${d.prefijo}db/datos_${marcaDeTiempo()}.db`
      d.fs.uploadFile($filesystem.fileFromPath(temporal), r.base)
      r.bytes += $os.stat(temporal).size()
      for (const vieja of copiasDeLaBase(d).slice(Math.max(1, cfg.getInt("conservar")))) d.fs.delete(vieja.key)
    }
  } catch (err) {
    r.estado = "error"
    r.error = explicar(err)
  } finally {
    if (temporal) {
      try { $os.remove(temporal) } catch { /* ya no está */ }
    }
    if (d) d.fs.close()
  }
  return r
}

// --- Estado e historial ---------------------------------------------------------

function anotar(app, r, manual) {
  const cfg = config(app)
  const ahora = new Date().toISOString()
  const p = r.destino
  cfg.set(`${p}_ultimo_intento`, ahora)
  if (r.estado === "ok") {
    cfg.set(`${p}_ultimo_ok`, ahora)
    cfg.set(`${p}_error`, "")
    cfg.set(`${p}_pendiente`, false)
  } else if (r.estado === "parcial") {
    cfg.set(`${p}_error`, "")
    cfg.set(`${p}_pendiente`, true)
  } else {
    cfg.set(`${p}_error`, r.error)
  }
  app.save(cfg)

  // Un respaldo largo avanza en varias tandas: se completa la misma fila.
  const anterior = app.findRecordsByFilter("respaldos", "destino = {:d}", "-created", 1, 0, { d: p })[0]
  let fila
  if (anterior && anterior.getString("estado") === "parcial") {
    fila = anterior
    fila.set("copiados", fila.getInt("copiados") + r.copiados)
    fila.set("bytes", fila.getInt("bytes") + r.bytes)
  } else {
    fila = new Record(app.findCollectionByNameOrId("respaldos"))
    fila.set("destino", p)
    fila.set("copiados", r.copiados)
    fila.set("bytes", r.bytes)
  }
  fila.set("estado", r.estado)
  fila.set("manual", manual || fila.getBool("manual"))
  fila.set("total", r.total)
  fila.set("base", r.base)
  fila.set("error", r.error)
  app.save(fila)

  app.db().newQuery(
    "DELETE FROM respaldos WHERE id NOT IN (SELECT id FROM respaldos ORDER BY created DESC LIMIT {:n})",
  ).bind({ n: HISTORIAL_MAXIMO }).execute()
}

function toca(cfg, destino) {
  const ahora = Date.now()
  if (cfg.getString(`${destino}_error`)) {
    const intento = Date.parse(cfg.getString(`${destino}_ultimo_intento`)) || 0
    if (ahora - intento < ESPERA_TRAS_ERROR) return false
  }
  if (cfg.getBool(`${destino}_pendiente`)) return true
  const ok = Date.parse(cfg.getString(`${destino}_ultimo_ok`)) || 0
  return ahora - ok >= cfg.getInt("cada_horas") * 3600 * 1000
}

function tomar(app) {
  const ahora = new Date()
  return app.db().newQuery(
    "UPDATE config_respaldo SET en_curso = {:ahora} WHERE en_curso = '' OR en_curso < {:vencido}",
  ).bind({ ahora: ahora.toISOString(), vencido: new Date(ahora.getTime() - BLOQUEO_VENCE).toISOString() })
    .execute().rowsAffected() > 0
}

function soltar(app) {
  app.db().newQuery("UPDATE config_respaldo SET en_curso = ''").execute()
}

/**
 * Respalda los destinos activos que correspondan (todos si `forzar`), sin pasar
 * de `presupuestoMs`; lo que falte sigue en la próxima vuelta.
 */
function ejecutar(app, { forzar = false, presupuestoMs = 50000, manual = false } = {}) {
  if (!tomar(app)) return { ocupado: true, resultados: [] }
  try {
    const limite = Date.now() + presupuestoMs
    const resultados = []
    for (const destino of DESTINOS) {
      const cfg = config(app)
      if (!cfg.getBool(`${destino}_activa`)) continue
      if (!forzar && !toca(cfg, destino)) continue
      if (Date.now() > limite - 3000) break
      const r = respaldar(app, cfg, destino, limite)
      anotar(app, r, manual)
      resultados.push(r)
    }
    return { ocupado: false, resultados }
  } finally {
    soltar(app)
  }
}

// Escribe, lee y borra un archivo de prueba.
function probar(app, destino) {
  let d
  try {
    d = abrir(app, config(app), destino)
  } catch (err) {
    throw new BadRequestError(explicar(err))
  }
  try {
    const clave = `${d.prefijo}prueba_conexion.txt`
    const texto = `Prueba del sistema de bautismos ${new Date().toISOString()}`
    d.fs.upload(texto, clave)
    const lector = d.fs.getReader(clave)
    const leido = readerToString(lector)
    lector.close()
    d.fs.delete(clave)
    if (leido !== texto) throw new Error("El archivo de prueba volvió distinto.")
    return { ok: true, destino: d.descripcion }
  } catch (err) {
    throw new BadRequestError(explicar(err))
  } finally {
    d.fs.close()
  }
}

function listarCopias(app, destino) {
  let d
  try {
    d = abrir(app, config(app), destino)
  } catch (err) {
    throw new BadRequestError(explicar(err))
  }
  try {
    return copiasDeLaBase(d).map((o) => ({ nombre: o.key.slice(d.prefijo.length), tam: o.size }))
  } catch (err) {
    throw new BadRequestError(explicar(err))
  } finally {
    d.fs.close()
  }
}

function estado(app) {
  const cfg = config(app)
  const resumen = { cada_horas: cfg.getInt("cada_horas"), en_curso: !!cfg.getString("en_curso") }
  for (const p of DESTINOS) {
    resumen[p] = {
      activa: cfg.getBool(`${p}_activa`),
      ultimo_ok: cfg.getString(`${p}_ultimo_ok`),
      ultimo_intento: cfg.getString(`${p}_ultimo_intento`),
      error: cfg.getString(`${p}_error`),
      pendiente: cfg.getBool(`${p}_pendiente`),
    }
  }
  resumen.carpeta.ruta = carpetaDestino(app, cfg)
  return resumen
}

// --- Restauración: bajar de la nube a una carpeta con la misma estructura -----
// Los datos de la nube salen de la configuración guardada o de variables de
// entorno RESPALDO_S3_* (para una PC nueva, ver instalador/restaurar.ps1).

function descargar(app, carpeta) {
  if (!carpeta) throw new Error("Indique la carpeta donde descargar el respaldo.")
  const env = (n) => $os.getenv(`RESPALDO_S3_${n}`)
  const datosNube = env("ENDPOINT") ? {
    endpoint: env("ENDPOINT"), region: env("REGION"), bucket: env("BUCKET"),
    prefijo: env("PREFIJO"), claveId: env("CLAVE_ID"), secreto: env("SECRETO"),
  } : undefined
  // En una PC recién instalada todavía no hay configuración: se usan las variables.
  const nube = abrir(app, datosNube ? null : config(app), "nube", datosNube)
  $os.mkdirAll(carpeta, 0o755)
  const local = $filesystem.local(carpeta)
  try {
    const copias = copiasDeLaBase(nube)
    if (!copias.length) throw new Error(`No hay copias de la base en ${nube.descripcion}.`)
    const objetos = nube.fs.list(`${nube.prefijo}storage/`)
    console.log(`Imágenes en la nube: ${objetos.length}. Bajando las que falten…`)
    let bajados = 0
    for (const o of [...objetos, copias[0]]) {
      const clave = o.key.slice(nube.prefijo.length)
      if (local.exists(clave) && local.attributes(clave).size === o.size) continue
      const lector = nube.fs.getReader(o.key)
      try {
        local.upload(toBytes(lector, 4 * 1024 * 1024 * 1024), clave)
      } finally {
        lector.close()
      }
      if (++bajados % 100 === 0) console.log(`  ${bajados} archivos…`)
    }
    console.log(`Listo: ${bajados} archivos nuevos. Base más reciente: ${copias[0].key.slice(nube.prefijo.length)}`)
  } finally {
    local.close()
    nube.fs.close()
  }
}

module.exports = { ejecutar, probar, listarCopias, estado, descargar, config, explicar }
