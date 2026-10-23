/// <reference path="../pb_data/types.d.ts" />

// 1. Las imágenes de los libros (y la firma y el sello) quedan protegidas: solo se
//    descargan con un token de archivo de un usuario con sesión iniciada.
// 2. Miniatura propia por página (JPEG ~20 KB generado en el navegador): las de
//    PocketBase para WebP salen como PNG de ~130 KB, pesadas para la galería.

const PROTEGIDOS = {
  paginas: ["imagen"],
  actas_bautismo: ["imagen_recorte"],
  parroquia: ["sello", "firma"],
}

function marcar(app, valor) {
  for (const [coleccion, campos] of Object.entries(PROTEGIDOS)) {
    const c = app.findCollectionByNameOrId(coleccion)
    for (const campo of campos) c.fields.getByName(campo).protected = valor
    app.save(c)
  }
}

migrate((app) => {
  marcar(app, true)

  const paginas = app.findCollectionByNameOrId("paginas")
  paginas.fields.add(new FileField({
    name: "miniatura",
    maxSelect: 1,
    maxSize: 512 * 1024,
    mimeTypes: ["image/jpeg", "image/webp"],
    protected: true,
  }))
  app.save(paginas)

  // El token de archivo dura una jornada de trabajo (por defecto son 3 minutos),
  // para que las URLs de las imágenes no cambien mientras se usa la galería.
  const usuarios = app.findCollectionByNameOrId("users")
  usuarios.fileToken.duration = 10 * 3600
  app.save(usuarios)
}, (app) => {
  marcar(app, false)
  const paginas = app.findCollectionByNameOrId("paginas")
  paginas.fields.removeByName("miniatura")
  app.save(paginas)
  const usuarios = app.findCollectionByNameOrId("users")
  usuarios.fileToken.duration = 180
  app.save(usuarios)
})
