/// <reference path="../pb_data/types.d.ts" />

// Cola de transcripción con IA. La lógica está en ia.js (cada handler corre en
// su propia VM y lo carga con require()).

// --- Trabajos: el estado y el resultado los decide solo el servidor ---------

onRecordValidate((e) => {
  const pagina = e.app.findRecordById("paginas", e.record.getString("pagina"))
  e.record.set("libro", pagina.getString("libro"))
  e.next()
}, "trabajos_ia")

onRecordCreateRequest((e) => {
  require(`${__hooks}/ia.js`).reiniciarTrabajo(e.record, e.auth?.id)
  e.next()
}, "trabajos_ia")

// Actualizar un trabajo = volver a enviarlo (con un recorte nuevo o el mismo).
onRecordUpdateRequest((e) => {
  e.record.set("pagina", e.record.original().getString("pagina"))
  e.record.set("posicion", e.record.original().getInt("posicion"))
  require(`${__hooks}/ia.js`).reiniciarTrabajo(e.record, e.auth?.id)
  e.next()
}, "trabajos_ia")

// --- Configuración ------------------------------------------------------------

// El uso, los errores y la clave los lleva el servidor (la clave es un campo
// oculto: PocketBase no deja escribirlo por la API común; ver /api/ia/clave).
onRecordUpdateRequest((e) => {
  const original = e.record.original()
  for (const campo of ["uso_fecha", "uso_dia", "ultimo_error", "proveedor", "clave_fin"]) {
    e.record.set(campo, original.get(campo))
  }
  e.next()
}, "config_ia")

// Guarda o quita ("") la clave de Gemini. Nunca se devuelve.
routerAdd("POST", "/api/ia/clave", (e) => {
  if (e.auth?.getString("rol") !== "admin") throw new ForbiddenError("Solo el administrador.")
  const clave = String(e.requestInfo().body["api_key"] ?? "").trim()
  if (clave.length > 300) throw new BadRequestError("La clave es demasiado larga.")
  const cfg = require(`${__hooks}/ia.js`).config(e.app)
  cfg.set("api_key", clave)
  cfg.set("clave_fin", clave ? clave.slice(-4) : "")
  cfg.set("ultimo_error", "")
  e.app.save(cfg)
  return e.json(200, { clave_fin: cfg.getString("clave_fin") })
}, $apis.requireAuth("users"))

// --- Tarea programada: cada minuto procesa lo que permita el límite ---------

// IA_SIN_CRON=1 la desactiva (pruebas automáticas, que procesan con /api/ia/procesar).
if (!$os.getenv("IA_SIN_CRON")) {
  cronAdd("cola_ia", "* * * * *", () => {
    try {
      require(`${__hooks}/ia.js`).procesarCola($app, 55000)
    } catch (err) {
      $app.logger().error("Cola de IA", "error", String(err))
    }
  })
}

// --- Rutas ------------------------------------------------------------------

// Resumen de la cola para la interfaz.
routerAdd("GET", "/api/ia/estado", (e) => {
  const filas = arrayOf(new DynamicModel({ estado: "", n: 0 }))
  e.app.db().newQuery("SELECT estado, COUNT(*) AS n FROM trabajos_ia GROUP BY estado").all(filas)
  const cola = { pendiente: 0, procesando: 0, listo: 0, error: 0 }
  for (const f of filas) cola[f.estado] = f.n
  // "listo" incluye los ya revisados; interesa cuántos esperan revisión.
  const sinRevisar = arrayOf(new DynamicModel({ n: 0 }))
  e.app.db().newQuery(
    `SELECT COUNT(*) AS n FROM trabajos_ia t WHERE t.estado = 'listo' AND NOT EXISTS
     (SELECT 1 FROM actas_bautismo a WHERE a.pagina = t.pagina AND a.posicion = t.posicion)`,
  ).all(sinRevisar)
  const cfg = require(`${__hooks}/ia.js`).config(e.app)
  return e.json(200, {
    activo: cfg.getBool("activo"),
    configurada: !!cfg.getString("api_key"),
    modelo: cfg.getString("modelo"),
    ultimo_error: cfg.getString("ultimo_error"),
    uso_dia: cfg.getString("uso_fecha") === new Date().toISOString().slice(0, 10) ? cfg.getInt("uso_dia") : 0,
    por_dia: cfg.getInt("por_dia"),
    cola: { ...cola, por_revisar: sinRevisar[0]?.n ?? 0 },
  })
}, $apis.requireAuth("users"))

// Procesar ahora, sin esperar a la tarea programada.
routerAdd("POST", "/api/ia/procesar", (e) => {
  if (e.auth?.getString("rol") !== "admin") throw new ForbiddenError("Solo el administrador.")
  return e.json(200, require(`${__hooks}/ia.js`).procesarCola(e.app, 50000))
}, $apis.requireAuth("users"))

// Comprueba la clave y devuelve los modelos disponibles para ella.
routerAdd("POST", "/api/ia/probar", (e) => {
  if (e.auth?.getString("rol") !== "admin") throw new ForbiddenError("Solo el administrador.")
  const ia = require(`${__hooks}/ia.js`)
  const cfg = ia.config(e.app)
  if (!cfg.getString("api_key")) throw new BadRequestError("Primero guarde la clave de Gemini.")
  return e.json(200, { modelos: ia.listarModelos(cfg) })
}, $apis.requireAuth("users"))

// Reintentar todos los trabajos con error.
routerAdd("POST", "/api/ia/reintentar", (e) => {
  if (e.auth?.getString("rol") !== "admin") throw new ForbiddenError("Solo el administrador.")
  const errores = e.app.findRecordsByFilter("trabajos_ia", "estado = 'error'", "", 0, 0)
  for (const t of errores) {
    t.set("estado", "pendiente")
    t.set("intentos", 0)
    t.set("error", "")
    e.app.save(t)
  }
  return e.json(200, { reintentados: errores.length })
}, $apis.requireAuth("users"))
