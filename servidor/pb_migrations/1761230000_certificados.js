/// <reference path="../pb_data/types.d.ts" />

// Fe de bautismo: cada certificado emitido queda registrado con un número
// correlativo por año y una copia de los datos tal como se imprimieron, para
// poder reimprimirlo igual aunque el acta se corrija después.
// No se editan ni se borran: un certificado con error se reemplaza emitiendo otro.

migrate((app) => {
  const actas = app.findCollectionByNameOrId("actas_bautismo")
  const usuarios = app.findCollectionByNameOrId("users")

  const certificados = new Collection({
    type: "base",
    name: "certificados",
    listRule: '@request.auth.id != ""',
    viewRule: '@request.auth.id != ""',
    createRule: '@request.auth.id != ""',
    updateRule: null,
    deleteRule: null,
    fields: [
      { name: "acta", type: "relation", collectionId: actas.id, maxSelect: 1, required: true, cascadeDelete: false },
      // Los asigna el servidor
      { name: "numero", type: "text", max: 20 },
      { name: "anio", type: "number", onlyInt: true },
      { name: "correlativo", type: "number", onlyInt: true },
      { name: "datos", type: "json", maxSize: 20000 },
      { name: "emitido_por", type: "relation", collectionId: usuarios.id, maxSelect: 1, cascadeDelete: false },
      // Los elige quien emite
      { name: "fines", type: "text", max: 200 },
      { name: "firmante", type: "text", max: 200 },
      { name: "cargo", type: "text", max: 120 },
      { name: "fecha_expedicion", type: "text", pattern: "^\\d{4}-\\d{2}-\\d{2}$" },
      { name: "created", type: "autodate", onCreate: true, onUpdate: false },
    ],
    indexes: [
      "CREATE UNIQUE INDEX idx_certificados_numero ON certificados (anio, correlativo)",
      "CREATE INDEX idx_certificados_acta ON certificados (acta)",
    ],
  })
  app.save(certificados)
}, (app) => {
  app.delete(app.findCollectionByNameOrId("certificados"))
})
