/// <reference path="../pb_data/types.d.ts" />

// Herramientas del piloto:
// 1. Observaciones: cualquier usuario anota un problema, una duda o una
//    sugerencia desde la pantalla donde está; el administrador las revisa y responde.
// 2. El administrador puede poner una contraseña nueva a otro usuario que la olvidó
//    (regla "manage" de PocketBase: no pide la contraseña anterior).

const ADMIN = '@request.auth.rol = "admin"'

migrate((app) => {
  const usuarios = app.findCollectionByNameOrId("users")
  usuarios.manageRule = ADMIN
  app.save(usuarios)

  const observaciones = new Collection({
    type: "base",
    name: "observaciones",
    listRule: `${ADMIN} || usuario = @request.auth.id`,
    viewRule: `${ADMIN} || usuario = @request.auth.id`,
    createRule: '@request.auth.id != ""',
    updateRule: ADMIN,
    deleteRule: ADMIN,
    fields: [
      { name: "usuario", type: "relation", collectionId: usuarios.id, maxSelect: 1, cascadeDelete: false },
      { name: "tipo", type: "select", values: ["problema", "duda", "sugerencia"], maxSelect: 1, required: true },
      { name: "texto", type: "text", required: true, max: 3000 },
      { name: "pantalla", type: "text", max: 300 },
      { name: "estado", type: "select", values: ["pendiente", "resuelta"], maxSelect: 1, required: true },
      { name: "respuesta", type: "text", max: 3000 },
      { name: "created", type: "autodate", onCreate: true, onUpdate: false },
      { name: "updated", type: "autodate", onCreate: true, onUpdate: true },
    ],
    indexes: ["CREATE INDEX idx_observaciones_estado ON observaciones (estado, created)"],
  })
  app.save(observaciones)
}, (app) => {
  app.delete(app.findCollectionByNameOrId("observaciones"))
  const usuarios = app.findCollectionByNameOrId("users")
  usuarios.manageRule = null
  app.save(usuarios)
})
