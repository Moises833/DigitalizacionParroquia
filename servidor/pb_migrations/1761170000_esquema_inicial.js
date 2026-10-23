/// <reference path="../pb_data/types.d.ts" />

// Esquema inicial del sistema de digitalización de actas de bautismo.
//
// Las fechas de las actas se guardan como texto "YYYY-MM-DD" (no como campo
// date) para evitar corrimientos por zona horaria: son fechas de calendario,
// no instantes.

const AUTENTICADO = '@request.auth.id != ""'
const ADMIN = '@request.auth.rol = "admin"'
const FECHA = "^\\d{4}-\\d{2}-\\d{2}$"
const IMAGENES = ["image/jpeg", "image/png", "image/webp"]

const creadoActualizado = [
  { name: "created", type: "autodate", onCreate: true, onUpdate: false },
  { name: "updated", type: "autodate", onCreate: true, onUpdate: true },
]

migrate((app) => {
  // --- Usuarios: se agrega el rol -------------------------------------------
  const usuarios = app.findCollectionByNameOrId("users")
  usuarios.fields.add(new SelectField({
    name: "rol",
    values: ["admin", "secretaria"],
    maxSelect: 1,
    required: true,
  }))
  usuarios.listRule = AUTENTICADO
  usuarios.viewRule = AUTENTICADO
  usuarios.createRule = ADMIN
  // Un usuario puede editar su perfil, pero nunca cambiarse el rol.
  usuarios.updateRule = `${ADMIN} || (id = @request.auth.id && @request.body.rol:isset = false)`
  usuarios.deleteRule = `${ADMIN} && id != @request.auth.id`
  app.save(usuarios)

  // --- Datos de la parroquia (un solo registro) -----------------------------
  const parroquia = new Collection({
    type: "base",
    name: "parroquia",
    listRule: AUTENTICADO,
    viewRule: AUTENTICADO,
    createRule: null,
    updateRule: ADMIN,
    deleteRule: null,
    fields: [
      { name: "nombre", type: "text", required: true, max: 200 },
      { name: "diocesis", type: "text", max: 200 },
      { name: "direccion", type: "text", max: 300 },
      { name: "ciudad", type: "text", max: 120 },
      { name: "telefono", type: "text", max: 60 },
      { name: "parroco", type: "text", max: 200 },
      { name: "membrete", type: "file", maxSelect: 1, maxSize: 2 * 1024 * 1024, mimeTypes: IMAGENES },
      { name: "sello", type: "file", maxSelect: 1, maxSize: 2 * 1024 * 1024, mimeTypes: IMAGENES },
      { name: "firma", type: "file", maxSelect: 1, maxSize: 2 * 1024 * 1024, mimeTypes: IMAGENES },
      ...creadoActualizado,
    ],
  })
  app.save(parroquia)

  const registroParroquia = new Record(parroquia)
  registroParroquia.set("nombre", "Parroquia (configurar nombre)")
  app.save(registroParroquia)

  // --- Libros ---------------------------------------------------------------
  const libros = new Collection({
    type: "base",
    name: "libros",
    listRule: AUTENTICADO,
    viewRule: AUTENTICADO,
    createRule: AUTENTICADO,
    updateRule: AUTENTICADO,
    deleteRule: ADMIN,
    fields: [
      { name: "numero", type: "text", required: true, max: 50 },
      { name: "sacramento", type: "select", values: ["bautismo"], maxSelect: 1, required: true },
      { name: "anio_inicio", type: "number", onlyInt: true, min: 1500, max: 2200 },
      { name: "anio_fin", type: "number", onlyInt: true, min: 1500, max: 2200 },
      { name: "observaciones", type: "text", max: 2000 },
      ...creadoActualizado,
    ],
    indexes: [
      "CREATE UNIQUE INDEX idx_libros_numero ON libros (sacramento, numero)",
    ],
  })
  app.save(libros)

  // --- Páginas (una foto por folio físico) ----------------------------------
  const paginas = new Collection({
    type: "base",
    name: "paginas",
    listRule: AUTENTICADO,
    viewRule: AUTENTICADO,
    createRule: AUTENTICADO,
    updateRule: AUTENTICADO,
    deleteRule: ADMIN,
    fields: [
      { name: "libro", type: "relation", collectionId: libros.id, maxSelect: 1, required: true, cascadeDelete: false },
      { name: "folio", type: "text", required: true, max: 20 },
      {
        name: "imagen", type: "file", maxSelect: 1, required: true,
        maxSize: 15 * 1024 * 1024, mimeTypes: IMAGENES, thumbs: ["400x0"],
      },
      { name: "estado", type: "select", values: ["pendiente", "segmentada", "completa"], maxSelect: 1, required: true },
      { name: "origen", type: "select", values: ["pc", "movil"], maxSelect: 1 },
      ...creadoActualizado,
    ],
    indexes: [
      "CREATE UNIQUE INDEX idx_paginas_folio ON paginas (libro, folio)",
    ],
  })
  app.save(paginas)

  // --- Actas de bautismo ----------------------------------------------------
  const actas = new Collection({
    type: "base",
    name: "actas_bautismo",
    listRule: AUTENTICADO,
    viewRule: AUTENTICADO,
    createRule: AUTENTICADO,
    updateRule: AUTENTICADO,
    deleteRule: ADMIN,
    fields: [
      // Ubicación física
      { name: "libro", type: "relation", collectionId: libros.id, maxSelect: 1, required: true, cascadeDelete: false },
      { name: "pagina", type: "relation", collectionId: paginas.id, maxSelect: 1, cascadeDelete: false },
      { name: "posicion", type: "number", onlyInt: true, min: 1, max: 6 },
      { name: "numero_acta", type: "text", max: 20 },
      // Recorte dentro de la página: { x, y, ancho, alto } en proporciones 0..1
      { name: "recorte", type: "json", maxSize: 2000 },
      { name: "imagen_recorte", type: "file", maxSelect: 1, maxSize: 5 * 1024 * 1024, mimeTypes: IMAGENES, thumbs: ["600x0"] },

      // Datos del bautizado
      { name: "nombres", type: "text", required: true, max: 150 },
      { name: "apellidos", type: "text", max: 150 },
      { name: "sexo", type: "select", values: ["M", "F"], maxSelect: 1 },
      { name: "fecha_nacimiento", type: "text", pattern: FECHA },
      { name: "lugar_nacimiento", type: "text", max: 200 },
      { name: "fecha_bautizo", type: "text", pattern: FECHA },

      // Personas relacionadas (texto tal cual figura en el libro)
      { name: "padre", type: "text", max: 200 },
      { name: "madre", type: "text", max: 200 },
      { name: "padrino", type: "text", max: 200 },
      { name: "madrina", type: "text", max: 200 },
      { name: "ministro", type: "text", max: 200 },

      // Anotaciones
      { name: "registro_civil", type: "text", max: 200 },
      { name: "observaciones", type: "text", max: 5000 },
      { name: "notas_marginales", type: "text", max: 5000 },

      // Control del flujo de trabajo
      { name: "estado", type: "select", values: ["borrador", "revisada", "verificada"], maxSelect: 1, required: true },
      { name: "origen_datos", type: "select", values: ["manual", "ia"], maxSelect: 1 },
      { name: "ia_respuesta", type: "json", maxSize: 50000 },
      { name: "creado_por", type: "relation", collectionId: usuarios.id, maxSelect: 1, cascadeDelete: false },
      { name: "revisado_por", type: "relation", collectionId: usuarios.id, maxSelect: 1, cascadeDelete: false },

      // Texto normalizado (minúsculas, sin acentos) que mantiene un hook
      { name: "busqueda", type: "text", max: 5000, hidden: false },
      ...creadoActualizado,
    ],
    indexes: [
      "CREATE INDEX idx_actas_libro_numero ON actas_bautismo (libro, numero_acta)",
      "CREATE INDEX idx_actas_fecha_bautizo ON actas_bautismo (fecha_bautizo)",
      "CREATE UNIQUE INDEX idx_actas_pagina_posicion ON actas_bautismo (pagina, posicion) WHERE pagina != '' AND posicion > 0",
    ],
  })
  app.save(actas)

  // --- Auditoría (solo la escriben los hooks) -------------------------------
  const auditoria = new Collection({
    type: "base",
    name: "auditoria",
    listRule: ADMIN,
    viewRule: ADMIN,
    createRule: null,
    updateRule: null,
    deleteRule: null,
    fields: [
      { name: "usuario", type: "relation", collectionId: usuarios.id, maxSelect: 1, cascadeDelete: false },
      { name: "accion", type: "select", values: ["crear", "editar", "eliminar"], maxSelect: 1, required: true },
      { name: "coleccion", type: "text", required: true, max: 100 },
      { name: "registro", type: "text", required: true, max: 50 },
      { name: "antes", type: "json", maxSize: 100000 },
      { name: "despues", type: "json", maxSize: 100000 },
      { name: "created", type: "autodate", onCreate: true, onUpdate: false },
    ],
    indexes: [
      "CREATE INDEX idx_auditoria_registro ON auditoria (coleccion, registro)",
    ],
  })
  app.save(auditoria)
}, (app) => {
  for (const nombre of ["auditoria", "actas_bautismo", "paginas", "libros", "parroquia"]) {
    app.delete(app.findCollectionByNameOrId(nombre))
  }
  const usuarios = app.findCollectionByNameOrId("users")
  usuarios.fields.removeByName("rol")
  app.save(usuarios)
})
