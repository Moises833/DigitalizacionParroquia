/// <reference path="../pb_data/types.d.ts" />

// Búsqueda por persona: el bautizado, los padres y los padrinos en campos
// normalizados (sin acentos, minúsculas) que mantiene el hook de main.pb.js.
// Además, índices para filtrar por libro y fecha de bautizo y ordenar.

const CAMPOS = { b_nombre: ["nombres", "apellidos"], b_padres: ["padre", "madre"], b_padrinos: ["padrino", "madrina"] }

function normalizar(texto) {
  return String(texto || "").normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase().replace(/\s+/g, " ").trim()
}

migrate((app) => {
  const actas = app.findCollectionByNameOrId("actas_bautismo")
  for (const nombre of Object.keys(CAMPOS)) {
    actas.fields.add(new TextField({ name: nombre, max: 1000 }))
  }
  actas.indexes.push("CREATE INDEX idx_actas_libro_fecha ON actas_bautismo (libro, fecha_bautizo)")
  app.save(actas)

  // Actas existentes.
  for (const acta of app.findAllRecords("actas_bautismo")) {
    for (const [campo, origen] of Object.entries(CAMPOS)) {
      acta.set(campo, origen.map((c) => normalizar(acta.getString(c))).filter(Boolean).join(" | "))
    }
    app.saveNoValidate(acta)
  }
}, (app) => {
  const actas = app.findCollectionByNameOrId("actas_bautismo")
  for (const nombre of Object.keys(CAMPOS)) actas.fields.removeByName(nombre)
  actas.indexes = actas.indexes.filter((i) => !i.includes("idx_actas_libro_fecha"))
  app.save(actas)
})
