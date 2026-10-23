/// <reference path="../pb_data/types.d.ts" />

// Respaldos incrementales a una carpeta (otro disco o un pendrive) y a la nube
// (cualquier almacenamiento compatible con S3, por ejemplo Backblaze B2).
// Ver pb_hooks/respaldo.js.

const ADMIN = '@request.auth.rol = "admin"'

// Estado de cada destino; lo escribe solo el servidor.
function camposEstado(destino) {
  return [
    { name: `${destino}_ultimo_ok`, type: "text", max: 40 },
    { name: `${destino}_ultimo_intento`, type: "text", max: 40 },
    { name: `${destino}_error`, type: "text", max: 1000 },
    { name: `${destino}_pendiente`, type: "bool" },
  ]
}

migrate((app) => {
  const config = new Collection({
    type: "base",
    name: "config_respaldo",
    listRule: ADMIN,
    viewRule: ADMIN,
    createRule: null,
    updateRule: ADMIN,
    deleteRule: null,
    fields: [
      { name: "cada_horas", type: "number", onlyInt: true, min: 1, max: 168, required: true },
      { name: "conservar", type: "number", onlyInt: true, min: 1, max: 365, required: true },
      // Carpeta: vacía = "respaldos" junto a pb_data (mejor otro disco)
      { name: "carpeta_activa", type: "bool" },
      { name: "carpeta_ruta", type: "text", max: 500 },
      // Nube compatible con S3
      { name: "nube_activa", type: "bool" },
      { name: "s3_endpoint", type: "text", max: 300 },
      { name: "s3_region", type: "text", max: 100 },
      { name: "s3_bucket", type: "text", max: 100 },
      { name: "s3_prefijo", type: "text", max: 100, pattern: "^[A-Za-z0-9._-]*$" },
      { name: "s3_clave_id", type: "text", max: 200 },
      { name: "s3_secreto", type: "text", max: 300, hidden: true },
      { name: "secreto_fin", type: "text", max: 10 },
      // Evita dos respaldos a la vez (fecha ISO o vacío)
      { name: "en_curso", type: "text", max: 40 },
      ...camposEstado("carpeta"),
      ...camposEstado("nube"),
    ],
  })
  app.save(config)

  const registro = new Record(config)
  registro.set("cada_horas", 8)
  registro.set("conservar", 30)
  registro.set("carpeta_activa", true)
  registro.set("s3_prefijo", "bautismos")
  app.save(registro)

  // Historial: una fila por respaldo (los parciales se van completando).
  const historial = new Collection({
    type: "base",
    name: "respaldos",
    listRule: ADMIN,
    viewRule: ADMIN,
    createRule: null,
    updateRule: null,
    deleteRule: null,
    fields: [
      { name: "destino", type: "select", values: ["carpeta", "nube"], maxSelect: 1, required: true },
      { name: "estado", type: "select", values: ["ok", "parcial", "error"], maxSelect: 1, required: true },
      { name: "manual", type: "bool" },
      { name: "copiados", type: "number", onlyInt: true },
      { name: "bytes", type: "number", onlyInt: true },
      { name: "total", type: "number", onlyInt: true },
      { name: "base", type: "text", max: 300 },
      { name: "error", type: "text", max: 1000 },
      { name: "created", type: "autodate", onCreate: true, onUpdate: false },
      { name: "updated", type: "autodate", onCreate: true, onUpdate: true },
    ],
    indexes: ["CREATE INDEX idx_respaldos_destino ON respaldos (destino, created)"],
  })
  app.save(historial)
}, (app) => {
  for (const nombre of ["respaldos", "config_respaldo"]) app.delete(app.findCollectionByNameOrId(nombre))
})
