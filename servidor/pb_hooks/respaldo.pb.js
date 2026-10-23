/// <reference path="../pb_data/types.d.ts" />

// Respaldos. La lógica está en respaldo.js (cada handler corre en su propia VM).

// --- Configuración ------------------------------------------------------------

// El estado y la clave secreta los lleva el servidor (la clave es un campo oculto;
// se cambia con /api/respaldos/secreto).
onRecordUpdateRequest((e) => {
  const original = e.record.original()
  const protegidos = ["en_curso", "secreto_fin"]
  for (const p of ["carpeta", "nube"]) {
    for (const sufijo of ["ultimo_ok", "ultimo_intento", "error", "pendiente"]) protegidos.push(`${p}_${sufijo}`)
  }
  for (const campo of protegidos) e.record.set(campo, original.get(campo))

  const ruta = e.record.getString("carpeta_ruta").trim()
  if (ruta && !$filepath.isAbs(ruta)) {
    throw new BadRequestError("La carpeta debe ser una ruta completa, por ejemplo E:\\Respaldos.")
  }
  e.record.set("carpeta_ruta", ruta)
  // Cambiar de destino empieza de cero: no hereda errores ni respaldos a medias.
  if (ruta !== original.getString("carpeta_ruta")) {
    for (const sufijo of ["ultimo_ok", "error", "pendiente"]) e.record.set(`carpeta_${sufijo}`, sufijo === "pendiente" ? false : "")
  }
  const nube = ["s3_endpoint", "s3_region", "s3_bucket", "s3_prefijo", "s3_clave_id"]
  if (nube.some((c) => e.record.getString(c) !== original.getString(c))) {
    for (const sufijo of ["ultimo_ok", "error", "pendiente"]) e.record.set(`nube_${sufijo}`, sufijo === "pendiente" ? false : "")
  }
  e.next()
}, "config_respaldo")

routerAdd("POST", "/api/respaldos/secreto", (e) => {
  if (e.auth?.getString("rol") !== "admin") throw new ForbiddenError("Solo el administrador.")
  const secreto = String(e.requestInfo().body["secreto"] ?? "").trim()
  if (secreto.length > 300) throw new BadRequestError("La clave es demasiado larga.")
  const cfg = require(`${__hooks}/respaldo.js`).config(e.app)
  cfg.set("s3_secreto", secreto)
  cfg.set("secreto_fin", secreto ? secreto.slice(-4) : "")
  cfg.set("nube_error", "")
  e.app.save(cfg)
  return e.json(200, { secreto_fin: cfg.getString("secreto_fin") })
}, $apis.requireAuth("users"))

// --- Tarea programada ---------------------------------------------------------

// Cada minuto revisa si toca respaldar. Así funciona aunque la PC se apague de
// noche: el respaldo se hace apenas se enciende y luego cada "cada_horas".
// RESPALDO_SIN_CRON=1 la desactiva (pruebas automáticas).
if (!$os.getenv("RESPALDO_SIN_CRON")) {
  cronAdd("respaldos", "* * * * *", () => {
    try {
      require(`${__hooks}/respaldo.js`).ejecutar($app, { presupuestoMs: 50000 })
    } catch (err) {
      $app.logger().error("Respaldos", "error", String(err))
    }
  })
}

// --- Rutas ------------------------------------------------------------------

// Resumen para todos (Inicio avisa si hace mucho que no hay respaldo).
routerAdd("GET", "/api/respaldos/estado", (e) => {
  const estado = require(`${__hooks}/respaldo.js`).estado(e.app)
  if (e.auth?.getString("rol") !== "admin") delete estado.carpeta.ruta
  return e.json(200, estado)
}, $apis.requireAuth("users"))

routerAdd("POST", "/api/respaldos/ejecutar", (e) => {
  if (e.auth?.getString("rol") !== "admin") throw new ForbiddenError("Solo el administrador.")
  return e.json(200, require(`${__hooks}/respaldo.js`).ejecutar(e.app, { forzar: true, presupuestoMs: 45000, manual: true }))
}, $apis.requireAuth("users"))

routerAdd("POST", "/api/respaldos/probar/{destino}", (e) => {
  if (e.auth?.getString("rol") !== "admin") throw new ForbiddenError("Solo el administrador.")
  const destino = e.request.pathValue("destino")
  if (!["carpeta", "nube"].includes(destino)) throw new BadRequestError("Destino desconocido.")
  return e.json(200, require(`${__hooks}/respaldo.js`).probar(e.app, destino))
}, $apis.requireAuth("users"))

routerAdd("GET", "/api/respaldos/copias/{destino}", (e) => {
  if (e.auth?.getString("rol") !== "admin") throw new ForbiddenError("Solo el administrador.")
  const destino = e.request.pathValue("destino")
  if (!["carpeta", "nube"].includes(destino)) throw new BadRequestError("Destino desconocido.")
  return e.json(200, require(`${__hooks}/respaldo.js`).listarCopias(e.app, destino))
}, $apis.requireAuth("users"))

// --- Comando para restaurar desde la nube -------------------------------------
// pocketbase.exe descargar-respaldo <carpeta>

$app.rootCmd.addCommand(new Command({
  use: "descargar-respaldo [carpeta]",
  short: "Baja de la nube la copia más reciente (base de datos e imágenes) a una carpeta",
  run: (cmd, args) => {
    try {
      require(`${__hooks}/respaldo.js`).descargar($app, args[0])
    } catch (err) {
      console.error(require(`${__hooks}/respaldo.js`).explicar(err))
      $os.exit(1)
    }
  },
}))
