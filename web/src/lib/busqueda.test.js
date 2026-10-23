import { test } from "node:test"
import assert from "node:assert/strict"
import { construirFiltro, interpretarAnios, palabras, resaltar, FILTROS_VACIOS } from "./busqueda.js"

const filtro = (extra) => construirFiltro({ ...FILTROS_VACIOS, ...extra })

test("palabras sin acentos ni mayúsculas", () => {
  assert.deepEqual(palabras("  José   NÚÑEZ "), ["jose", "nunez"])
  assert.deepEqual(palabras(""), [])
})

test("sin criterios no hay filtro", () => {
  assert.equal(filtro({}).expresion, "")
})

test("cada palabra del nombre debe aparecer, en cualquier orden", () => {
  const r = filtro({ nombre: "López María" })
  assert.equal(r.expresion, "b_nombre ~ {:p0} && b_nombre ~ {:p1}")
  assert.deepEqual(r.parametros, { p0: "lopez", p1: "maria" })
})

test("padres, padrinos, libro, folio y número se combinan", () => {
  const r = filtro({ padres: "Ruiz", padrinos: "castillo", libro: "abc", folio: " 12 ", numero: "34" })
  assert.equal(r.expresion,
    "b_padres ~ {:p0} && b_padrinos ~ {:p1} && libro = {:p2} && pagina.folio = {:p3} && numero_acta = {:p4}")
  assert.equal(r.parametros.p3, "12")
})

test("año exacto o rango", () => {
  assert.deepEqual(interpretarAnios("1981"), [1981, 1981])
  assert.deepEqual(interpretarAnios("1985-1980"), [1980, 1985])
  assert.deepEqual(interpretarAnios("1980 a 1985"), [1980, 1985])
  assert.equal(interpretarAnios(""), null)
  assert.equal(interpretarAnios("81"), false)
  const r = filtro({ anio: "1981" })
  assert.equal(r.expresion, "fecha_bautizo >= {:p0} && fecha_bautizo <= {:p1}")
  assert.deepEqual(r.parametros, { p0: "1981-01-01", p1: "1981-12-31" })
  assert.ok(filtro({ anio: "ochenta" }).error)
})

test("resalta sin importar acentos", () => {
  assert.deepEqual(resaltar("María López", ["maria"]), [
    { texto: "María", marcado: true }, { texto: " López", marcado: false },
  ])
  assert.deepEqual(resaltar("Núñez", []), [{ texto: "Núñez", marcado: false }])
  const t = resaltar("Ana Ana", ["ana"])
  assert.equal(t.filter((x) => x.marcado).length, 2)
})
