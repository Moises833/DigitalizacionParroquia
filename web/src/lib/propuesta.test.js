import { test } from "node:test"
import assert from "node:assert/strict"
import { propuestaADatos } from "./propuesta.js"

const base = {
  numero_acta: "34", nombres: "María Fernanda", apellidos: "López Ruiz", sexo: "F",
  fecha_nacimiento: "1981-03-03", fecha_nacimiento_texto: "tres de marzo de mil novecientos ochenta y uno",
  fecha_bautizo: "1981-05-21", fecha_bautizo_texto: "veintiuno de mayo de mil novecientos ochenta y uno",
  lugar_nacimiento: "Managua", padre: "José López", madre: "", padrino: "Pedro Castillo", madrina: "",
  ministro: "P. Juan Pérez", registro_civil: "", notas_marginales: "", observaciones: "", campos_dudosos: [],
}

test("campos leídos quedan marcados como de la IA", () => {
  const { datos, marcas } = propuestaADatos(base)
  assert.equal(datos.nombres, "María Fernanda")
  assert.equal(datos.fecha_bautizo, "1981-05-21")
  assert.equal(marcas.nombres, "ia")
  assert.equal(marcas.fecha_nacimiento, "ia")
  assert.equal(datos.madre, undefined) // vacío: se deja para completar
  assert.equal(marcas.madre, undefined)
})

test("campos dudosos, aunque estén vacíos", () => {
  const { datos, marcas } = propuestaADatos({ ...base, campos_dudosos: ["padrino", "madre"] })
  assert.equal(datos.padrino, "Pedro Castillo")
  assert.equal(marcas.padrino, "dudoso")
  assert.equal(marcas.madre, "dudoso")
})

test("la fecha se toma del texto leído y se marca si no coincide", () => {
  const { datos, marcas } = propuestaADatos({ ...base, fecha_bautizo: "1981-05-12" })
  assert.equal(datos.fecha_bautizo, "1981-05-21")
  assert.equal(marcas.fecha_bautizo, "dudoso")
})

test("si el texto de la fecha no se entiende se usa la de la IA, como dudosa si había texto", () => {
  const { datos, marcas } = propuestaADatos({ ...base, fecha_nacimiento_texto: "3 marzo 81" })
  assert.equal(datos.fecha_nacimiento, "1981-03-03")
  assert.equal(marcas.fecha_nacimiento, "ia")
  const sinFecha = propuestaADatos({ ...base, fecha_nacimiento: "", fecha_nacimiento_texto: "ilegible" })
  assert.equal(sinFecha.datos.fecha_nacimiento, undefined)
  assert.equal(sinFecha.marcas.fecha_nacimiento, "dudoso")
})

test("sin propuesta no hay datos", () => {
  assert.deepEqual(propuestaADatos(null), { datos: {}, marcas: {} })
})
