import { test } from "node:test"
import assert from "node:assert/strict"
import { precisionIA, porcentaje, duracion, porHora } from "./informe.js"

const respuesta = (extra) => ({
  nombres: "María", apellidos: "Pérez", padre: "Juan Pérez", madre: "", fecha_bautizo: "1981-05-21",
  fecha_bautizo_texto: "veintiuno de mayo de mil novecientos ochenta y uno", campos_dudosos: ["padre"], ...extra,
})

test("precisión de la IA por campo", () => {
  const r = precisionIA([
    // nombres aceptado, apellidos corregido, padre (dudoso) aceptado, madre agregada, fecha aceptada
    { estado: "revisada", nombres: "María", apellidos: "Pérez Gil", padre: "Juan  Pérez", madre: "Ana", fecha_bautizo: "1981-05-21", ia_respuesta: respuesta() },
    // en borrador: todavía no cuenta
    { estado: "borrador", nombres: "Otra", ia_respuesta: respuesta() },
    // sin propuesta: no cuenta
    { estado: "revisada", nombres: "Manual" },
  ])
  assert.equal(r.actas, 1)
  const campo = (c) => r.campos.find((x) => x.campo === c)
  assert.deepEqual(
    [campo("nombres").aceptados, campo("apellidos").corregidos, campo("padre").aceptados, campo("madre").agregados, campo("fecha_bautizo").aceptados],
    [1, 1, 1, 1, 1],
  )
  assert.equal(r.total.propuestos, 4)
  assert.equal(r.total.aceptados, 3)
  assert.equal(r.porMarca.dudoso.propuestos, 1)
  assert.equal(r.porMarca.ia.corregidos, 1)
})

test("formatos", () => {
  assert.equal(porcentaje(87, 100), "87 %")
  assert.equal(porcentaje(1, 0), "—")
  assert.equal(duracion(45), "45 min")
  assert.equal(duracion(135), "2 h 15 min")
  assert.equal(duracion(120), "2 h")
  assert.equal(porHora(30, 120), "15")
  assert.equal(porHora(5, 10), "—")
})
