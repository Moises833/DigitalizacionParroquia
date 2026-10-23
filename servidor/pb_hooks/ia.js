// Transcripción de actas con Gemini. Lo usan ia.pb.js (tarea programada y rutas).
// Para cambiar de proveedor basta con otra función equivalente a pedirGemini().

// Permite apuntar a un servidor simulado en las pruebas (scripts/prueba_ia.py).
const urlBase = () => $os.getenv("IA_URL_BASE") || "https://generativelanguage.googleapis.com"

const CAMPOS = {
  numero_acta: 20, nombres: 150, apellidos: 150, sexo: 1,
  fecha_nacimiento: 10, fecha_nacimiento_texto: 200, lugar_nacimiento: 200,
  fecha_bautizo: 10, fecha_bautizo_texto: 200,
  padre: 200, madre: 200, padrino: 200, madrina: 200, ministro: 200,
  registro_civil: 200, notas_marginales: 5000, observaciones: 5000, texto_completo: 10000,
}

const INSTRUCCIONES = `Eres paleógrafo y transcribes libros sacramentales de parroquias católicas de habla hispana.
La imagen muestra UNA sola acta de bautismo manuscrita (normalmente en letra cursiva), recortada de una página de un libro parroquial.

Reglas:
- Transcribe los nombres exactamente como están escritos. No inventes ni completes datos que no se lean.
- Si un dato no aparece o no se puede leer con seguridad, deja el campo vacío ("") y agrega el nombre del campo a campos_dudosos. Agrega también a campos_dudosos cualquier campo que hayas leído con dudas.
- Fechas: copia en fecha_bautizo_texto y fecha_nacimiento_texto el texto tal como está escrito (suelen estar en letras, por ejemplo "veintiuno de mayo de mil novecientos ochenta y uno") y escribe en fecha_bautizo y fecha_nacimiento la misma fecha en formato AAAA-MM-DD. Si no puedes determinar día, mes y año completos, deja vacío el campo AAAA-MM-DD.
- sexo: "M" si es varón, "F" si es mujer (deducido del texto, por ejemplo "un niño" o "una niña", o del nombre); "" si no se sabe.
- numero_acta: el número del acta, si figura (suele estar en el margen o al inicio).
- nombres: nombres de pila del bautizado; apellidos: sus apellidos.
- padre y madre: nombre completo de cada uno. padrino y madrina: nombre completo de cada uno.
- ministro: el sacerdote o diácono que administró el bautismo.
- registro_civil: número o datos de inscripción en el registro civil, si se mencionan.
- notas_marginales: lo escrito en el margen del acta (por ejemplo, matrimonio o confirmación posteriores).
- observaciones: otros datos relevantes, breves (abuelos, legitimidad, aclaraciones).
- texto_completo: la transcripción completa del acta, línea por línea.`

const texto = { type: "STRING" }
const ESQUEMA = {
  type: "OBJECT",
  properties: {
    ...Object.fromEntries(Object.keys(CAMPOS).map((c) => [c, texto])),
    campos_dudosos: { type: "ARRAY", items: texto },
  },
  required: [...Object.keys(CAMPOS), "campos_dudosos"],
}

// --- Utilidades -----------------------------------------------------------

const ALFABETO = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/"

function base64(bytes) {
  const partes = []
  const n = bytes.length
  let i = 0
  for (; i + 2 < n; i += 3) {
    const v = (bytes[i] << 16) | (bytes[i + 1] << 8) | bytes[i + 2]
    partes.push(ALFABETO[v >> 18] + ALFABETO[(v >> 12) & 63] + ALFABETO[(v >> 6) & 63] + ALFABETO[v & 63])
  }
  if (i < n) {
    const v = (bytes[i] << 16) | ((i + 1 < n ? bytes[i + 1] : 0) << 8)
    partes.push(ALFABETO[v >> 18] + ALFABETO[(v >> 12) & 63] + (i + 1 < n ? ALFABETO[(v >> 6) & 63] : "=") + "=")
  }
  return partes.join("")
}

const fechaIso = (ms) => new Date(ms).toISOString().replace("T", " ")
const hoy = () => new Date().toISOString().slice(0, 10)

function mimeDe(nombre) {
  const ext = String(nombre).split(".").pop().toLowerCase()
  return { jpg: "image/jpeg", jpeg: "image/jpeg", png: "image/png", webp: "image/webp" }[ext] || "image/jpeg"
}

function leerImagen(app, trabajo) {
  const fs = app.newFilesystem()
  try {
    const lector = fs.getReader(trabajo.baseFilesPath() + "/" + trabajo.getString("imagen"))
    try {
      return toBytes(lector)
    } finally {
      lector.close()
    }
  } finally {
    fs.close()
  }
}

function config(app) {
  return app.findFirstRecordByFilter("config_ia", "id != ''")
}

// Deja solo los campos esperados, recortados y con fechas válidas.
function normalizarResultado(datos) {
  const r = {}
  for (const [campo, max] of Object.entries(CAMPOS)) {
    r[campo] = String(datos?.[campo] ?? "").trim().slice(0, max)
  }
  const sexo = r.sexo.toUpperCase()
  r.sexo = sexo === "M" || sexo === "F" ? sexo : ""
  const dudosos = Array.isArray(datos?.campos_dudosos) ? datos.campos_dudosos.map(String) : []
  // "1981-13-40" tiene el formato pero no es una fecha.
  const fechaReal = (iso) => {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso)
    if (!m) return false
    const d = new Date(Date.UTC(+m[1], +m[2] - 1, +m[3]))
    return d.getUTCFullYear() === +m[1] && d.getUTCMonth() === +m[2] - 1 && d.getUTCDate() === +m[3]
  }
  for (const campo of ["fecha_nacimiento", "fecha_bautizo"]) {
    if (r[campo] && !fechaReal(r[campo])) {
      r[campo] = ""
      dudosos.push(campo)
    }
  }
  r.campos_dudosos = [...new Set(dudosos.filter((c) => c in CAMPOS))]
  return r
}

// --- Gemini ---------------------------------------------------------------

function mensajeGemini(res) {
  try {
    return res.json?.error?.message || toString(res.body).slice(0, 300)
  } catch {
    return `HTTP ${res.statusCode}`
  }
}

/**
 * Devuelve { ok, datos } o { ok: false, mensaje, tipo } con tipo:
 * "limite" (esperar y reintentar), "clave" (configuración inválida) o "fallo".
 */
function pedirGemini(cfg, bytes, mime) {
  const modelo = cfg.getString("modelo") || "gemini-2.5-flash"
  let res
  try {
    res = $http.send({
      url: `${urlBase()}/v1beta/models/${encodeURIComponent(modelo)}:generateContent`,
      method: "POST",
      headers: { "Content-Type": "application/json", "x-goog-api-key": cfg.getString("api_key") },
      timeout: 120,
      body: JSON.stringify({
        contents: [{ role: "user", parts: [{ text: INSTRUCCIONES }, { inline_data: { mime_type: mime, data: base64(bytes) } }] }],
        generationConfig: { temperature: 0, responseMimeType: "application/json", responseSchema: ESQUEMA },
      }),
    })
  } catch (err) {
    return { ok: false, tipo: "fallo", mensaje: `Sin conexión con Gemini: ${err}` }
  }

  if (res.statusCode === 429) return { ok: false, tipo: "limite", mensaje: "Se alcanzó el límite de uso de Gemini; se reintentará más tarde." }
  if (res.statusCode === 400 && /api key|API_KEY/i.test(mensajeGemini(res))) {
    return { ok: false, tipo: "clave", mensaje: "La clave de Gemini no es válida." }
  }
  if (res.statusCode === 401 || res.statusCode === 403) {
    return { ok: false, tipo: "clave", mensaje: `Gemini rechazó la clave: ${mensajeGemini(res)}` }
  }
  if (res.statusCode === 404) {
    return { ok: false, tipo: "clave", mensaje: `El modelo "${modelo}" no está disponible. Elija otro en Inteligencia artificial.` }
  }
  if (res.statusCode !== 200) return { ok: false, tipo: "fallo", mensaje: `Gemini respondió ${res.statusCode}: ${mensajeGemini(res)}` }

  const candidato = res.json?.candidates?.[0]
  const bloqueo = res.json?.promptFeedback?.blockReason
  if (!candidato || bloqueo) return { ok: false, tipo: "fallo", mensaje: `Gemini no devolvió respuesta (${bloqueo || "sin candidatos"}).` }
  const salida = (candidato.content?.parts || []).map((p) => p.text || "").join("")
  try {
    return { ok: true, modelo, datos: normalizarResultado(JSON.parse(salida)) }
  } catch {
    return { ok: false, tipo: "fallo", mensaje: `Respuesta de Gemini ilegible (${candidato.finishReason || "?"}).` }
  }
}

function listarModelos(cfg) {
  const res = $http.send({
    url: `${urlBase()}/v1beta/models?pageSize=200`,
    method: "GET",
    headers: { "x-goog-api-key": cfg.getString("api_key") },
    timeout: 30,
  })
  if (res.statusCode !== 200) throw new BadRequestError(`Gemini respondió ${res.statusCode}: ${mensajeGemini(res)}`)
  return (res.json?.models || [])
    .filter((m) => (m.supportedGenerationMethods || []).includes("generateContent"))
    .map((m) => String(m.name).replace(/^models\//, ""))
    .filter((n) => /gemini/i.test(n))
}

// --- Cola -----------------------------------------------------------------

function contarUso(app, cfg) {
  if (cfg.getString("uso_fecha") !== hoy()) {
    cfg.set("uso_fecha", hoy())
    cfg.set("uso_dia", 0)
  }
  cfg.set("uso_dia", cfg.getInt("uso_dia") + 1)
}

function marcarError(app, cfg, mensaje) {
  cfg.set("ultimo_error", mensaje)
  app.save(cfg)
}

// Toma el siguiente trabajo pendiente de forma atómica (varias ejecuciones a la vez
// no procesan el mismo trabajo). `vistos`: los ya intentados en esta ejecución,
// para que un recorte que falla no se reintente enseguida.
function tomarSiguiente(app, vistos) {
  const filas = arrayOf(new DynamicModel({ id: "" }))
  app.db().newQuery(
    "SELECT id FROM trabajos_ia WHERE estado = 'pendiente' ORDER BY intentos, created LIMIT {:n}",
  ).bind({ n: vistos.length + 5 }).all(filas)
  for (const fila of filas) {
    if (vistos.includes(fila.id)) continue
    const r = app.db().newQuery(
      "UPDATE trabajos_ia SET estado = 'procesando', updated = {:ahora} WHERE id = {:id} AND estado = 'pendiente'",
    ).bind({ id: fila.id, ahora: fechaIso(Date.now()) }).execute()
    if (r.rowsAffected() === 1) {
      const trabajo = app.findRecordById("trabajos_ia", fila.id)
      app.save(trabajo) // avisa por tiempo real que empezó
      return trabajo
    }
  }
  return null
}

/**
 * Procesa trabajos durante a lo sumo `presupuestoMs`, respetando el límite por
 * minuto y por día configurado. Devuelve un resumen.
 */
function procesarCola(app, presupuestoMs) {
  const inicio = Date.now()
  const resumen = { procesados: 0, errores: 0, motivo: "" }
  let cfg = config(app)
  if (!cfg.getBool("activo")) return { ...resumen, motivo: "La IA está desactivada." }
  if (!cfg.getString("api_key")) return { ...resumen, motivo: "Falta la clave de Gemini." }

  // Trabajos que quedaron a medias (servidor apagado durante la consulta).
  app.db().newQuery(
    "UPDATE trabajos_ia SET estado = 'pendiente' WHERE estado = 'procesando' AND updated < {:limite}",
  ).bind({ limite: fechaIso(Date.now() - 10 * 60 * 1000) }).execute()

  const espera = Math.ceil(60000 / Math.max(1, cfg.getInt("por_minuto") || 8))
  const vistos = []
  while (Date.now() - inicio + 20000 < presupuestoMs) {
    cfg = config(app)
    if (cfg.getString("uso_fecha") === hoy() && cfg.getInt("uso_dia") >= (cfg.getInt("por_dia") || 200)) {
      resumen.motivo = "Se alcanzó el máximo de consultas por día."
      break
    }
    const trabajo = tomarSiguiente(app, vistos)
    if (!trabajo) break
    vistos.push(trabajo.id)

    let respuesta
    try {
      respuesta = pedirGemini(cfg, leerImagen(app, trabajo), mimeDe(trabajo.getString("imagen")))
    } catch (err) {
      respuesta = { ok: false, tipo: "fallo", mensaje: String(err) }
    }
    contarUso(app, cfg)

    if (respuesta.ok) {
      trabajo.set("estado", "listo")
      trabajo.set("resultado", respuesta.datos)
      trabajo.set("modelo", respuesta.modelo)
      trabajo.set("error", "")
      app.save(trabajo)
      cfg.set("ultimo_error", "")
      app.save(cfg)
      resumen.procesados++
    } else if (respuesta.tipo === "limite" || respuesta.tipo === "clave") {
      // No es culpa del recorte: vuelve a la cola sin contar el intento.
      trabajo.set("estado", "pendiente")
      app.save(trabajo)
      marcarError(app, cfg, respuesta.mensaje)
      resumen.motivo = respuesta.mensaje
      break
    } else {
      const intentos = trabajo.getInt("intentos") + 1
      trabajo.set("intentos", intentos)
      trabajo.set("estado", intentos >= 3 ? "error" : "pendiente")
      trabajo.set("error", respuesta.mensaje)
      app.save(trabajo)
      marcarError(app, cfg, respuesta.mensaje)
      resumen.errores++
    }

    if (Date.now() - inicio + espera + 20000 >= presupuestoMs) break
    sleep(espera)
  }
  return resumen
}

// Al crear o volver a enviar un trabajo, el cliente no decide su estado.
function reiniciarTrabajo(record, usuarioId) {
  record.set("estado", "pendiente")
  record.set("resultado", null)
  record.set("error", "")
  record.set("intentos", 0)
  record.set("modelo", "")
  record.set("creado_por", usuarioId ?? "")
}

module.exports = { procesarCola, listarModelos, normalizarResultado, reiniciarTrabajo, base64, config }
