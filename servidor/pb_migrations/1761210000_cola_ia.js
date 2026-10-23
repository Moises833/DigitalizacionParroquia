/// <reference path="../pb_data/types.d.ts" />

// Transcripción con IA.
// - config_ia: un único registro con el proveedor, el modelo y la clave (oculta:
//   la API nunca la devuelve). Solo el administrador la modifica.
// - trabajos_ia: un recorte de acta (pagina + posicion) esperando a la IA. El
//   navegador crea el recorte; el servidor lo envía a Gemini desde una tarea
//   programada y guarda la propuesta. El acta se crea recién cuando una persona
//   revisa la propuesta en el formulario y la guarda.

const AUTENTICADO = '@request.auth.id != ""'
const ADMIN = '@request.auth.rol = "admin"'
const IMAGENES = ["image/jpeg", "image/png", "image/webp"]

const creadoActualizado = [
  { name: "created", type: "autodate", onCreate: true, onUpdate: false },
  { name: "updated", type: "autodate", onCreate: true, onUpdate: true },
]

migrate((app) => {
  const config = new Collection({
    type: "base",
    name: "config_ia",
    listRule: AUTENTICADO,
    viewRule: AUTENTICADO,
    createRule: null,
    updateRule: ADMIN,
    deleteRule: null,
    fields: [
      { name: "proveedor", type: "select", values: ["gemini"], maxSelect: 1, required: true },
      { name: "modelo", type: "text", max: 100 },
      { name: "api_key", type: "text", max: 300, hidden: true },
      { name: "clave_fin", type: "text", max: 10 }, // últimos caracteres, para reconocerla
      { name: "activo", type: "bool" },
      { name: "por_minuto", type: "number", onlyInt: true, min: 1, max: 60 },
      { name: "por_dia", type: "number", onlyInt: true, min: 1, max: 100000 },
      { name: "uso_fecha", type: "text", max: 10 },
      { name: "uso_dia", type: "number", onlyInt: true, min: 0 },
      { name: "ultimo_error", type: "text", max: 1000 },
      ...creadoActualizado,
    ],
  })
  app.save(config)

  const registro = new Record(config)
  registro.set("proveedor", "gemini")
  registro.set("modelo", "gemini-2.5-flash")
  registro.set("activo", false)
  registro.set("por_minuto", 8)
  registro.set("por_dia", 200)
  registro.set("uso_dia", 0)
  app.save(registro)

  const paginas = app.findCollectionByNameOrId("paginas")
  const libros = app.findCollectionByNameOrId("libros")
  const usuarios = app.findCollectionByNameOrId("users")

  const trabajos = new Collection({
    type: "base",
    name: "trabajos_ia",
    listRule: AUTENTICADO,
    viewRule: AUTENTICADO,
    createRule: AUTENTICADO,
    updateRule: AUTENTICADO, // solo para volver a enviar; el estado lo fija el servidor
    deleteRule: ADMIN,
    fields: [
      { name: "pagina", type: "relation", collectionId: paginas.id, maxSelect: 1, required: true, cascadeDelete: true },
      { name: "libro", type: "relation", collectionId: libros.id, maxSelect: 1, cascadeDelete: true },
      { name: "posicion", type: "number", onlyInt: true, min: 1, max: 6, required: true },
      { name: "recorte", type: "json", maxSize: 2000 },
      {
        name: "imagen", type: "file", maxSelect: 1, required: true, protected: true,
        maxSize: 5 * 1024 * 1024, mimeTypes: IMAGENES,
      },
      { name: "estado", type: "select", values: ["pendiente", "procesando", "listo", "error"], maxSelect: 1, required: true },
      { name: "resultado", type: "json", maxSize: 50000 },
      { name: "error", type: "text", max: 1000 },
      { name: "intentos", type: "number", onlyInt: true, min: 0 },
      { name: "modelo", type: "text", max: 100 },
      { name: "creado_por", type: "relation", collectionId: usuarios.id, maxSelect: 1, cascadeDelete: false },
      ...creadoActualizado,
    ],
    indexes: [
      "CREATE UNIQUE INDEX idx_trabajos_pagina_posicion ON trabajos_ia (pagina, posicion)",
      "CREATE INDEX idx_trabajos_estado ON trabajos_ia (estado, created)",
    ],
  })
  app.save(trabajos)
}, (app) => {
  app.delete(app.findCollectionByNameOrId("trabajos_ia"))
  app.delete(app.findCollectionByNameOrId("config_ia"))
})
