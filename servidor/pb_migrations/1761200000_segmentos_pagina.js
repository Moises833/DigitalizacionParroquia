/// <reference path="../pb_data/types.d.ts" />

// Recuadros de cada acta dentro de la página: [{ x, y, ancho, alto }] en proporciones
// 0..1, en orden de posición. Se guardan en la página para no perder el trabajo
// de segmentación aunque todavía no se haya transcrito ninguna acta.

migrate((app) => {
  const paginas = app.findCollectionByNameOrId("paginas")
  paginas.fields.add(new JSONField({ name: "segmentos", maxSize: 5000 }))
  app.save(paginas)
}, (app) => {
  const paginas = app.findCollectionByNameOrId("paginas")
  paginas.fields.removeByName("segmentos")
  app.save(paginas)
})
