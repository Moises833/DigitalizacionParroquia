/// <reference path="../pb_data/types.d.ts" />

// Vinculación de teléfonos: la PC abre una sesión de captura para un libro y
// muestra un QR con un token. El teléfono sube fotos con ese token, sin cuenta.
// Solo se guarda el hash del token; todas las escrituras pasan por rutas propias
// (pb_hooks/captura.pb.js), por eso las reglas de escritura son null.

migrate((app) => {
  const libros = app.findCollectionByNameOrId("libros")
  const usuarios = app.findCollectionByNameOrId("users")

  const sesiones = new Collection({
    type: "base",
    name: "sesiones_captura",
    listRule: '@request.auth.rol = "admin" || creado_por = @request.auth.id',
    viewRule: '@request.auth.rol = "admin" || creado_por = @request.auth.id',
    createRule: null,
    updateRule: null,
    deleteRule: null,
    fields: [
      { name: "libro", type: "relation", collectionId: libros.id, maxSelect: 1, required: true, cascadeDelete: true },
      { name: "token_hash", type: "text", required: true, hidden: true, max: 128 },
      { name: "folio_siguiente", type: "number", onlyInt: true, min: 0 },
      { name: "expira", type: "date", required: true },
      { name: "activa", type: "bool" },
      { name: "fotos_recibidas", type: "number", onlyInt: true, min: 0 },
      { name: "creado_por", type: "relation", collectionId: usuarios.id, maxSelect: 1, required: true, cascadeDelete: true },
      { name: "created", type: "autodate", onCreate: true, onUpdate: false },
      { name: "updated", type: "autodate", onCreate: true, onUpdate: true },
    ],
    indexes: [
      "CREATE UNIQUE INDEX idx_sesiones_token ON sesiones_captura (token_hash)",
    ],
  })
  app.save(sesiones)
}, (app) => {
  app.delete(app.findCollectionByNameOrId("sesiones_captura"))
})
