import { test } from "node:test"
import assert from "node:assert/strict"
import { interpretarFecha, formatearFecha, numeroEnLetras } from "./fechas.js"

test("números en letras", () => {
  assert.equal(numeroEnLetras("mil novecientos ochenta y uno"), 1981)
  assert.equal(numeroEnLetras("mil ochocientos noventa y nueve"), 1899)
  assert.equal(numeroEnLetras("dos mil cinco"), 2005)
  assert.equal(numeroEnLetras("dos mil veintiséis"), 2026)
  assert.equal(numeroEnLetras("veintiuno"), 21)
  assert.equal(numeroEnLetras("veinte y uno"), 21)
  assert.equal(numeroEnLetras("diez y seis"), 16)
  assert.equal(numeroEnLetras("treinta y uno"), 31)
  assert.equal(numeroEnLetras("primero"), 1)
  assert.equal(numeroEnLetras("palabra"), null)
})

test("fechas en letras, como en los libros", () => {
  const casos = {
    "veintiuno de mayo de mil novecientos ochenta y uno": "1981-05-21",
    "veintiuno de febrero de mil novecientos ochenta y uno": "1981-02-21",
    "tres de julio de mil novecientos setenta y ocho": "1978-07-03",
    "veinticuatro de septiembre de mil novecientos ochenta": "1980-09-24",
    "veintidos de septiembre de mil novecientos setenta y nueve": "1979-09-22",
    "Veintidós de Septiembre de Mil Novecientos Setenta y Nueve": "1979-09-22",
    "el primero de enero de mil novecientos cincuenta": "1950-01-01",
    "treinta y uno de diciembre de mil ochocientos noventa y nueve": "1899-12-31",
    "quince de setiembre del año mil novecientos sesenta": "1960-09-15",
    "doce de octubre de dos mil cinco": "2005-10-12",
  }
  for (const [texto, esperado] of Object.entries(casos)) {
    assert.equal(interpretarFecha(texto), esperado, texto)
  }
})

test("fechas con números", () => {
  assert.equal(interpretarFecha("21/05/1981"), "1981-05-21")
  assert.equal(interpretarFecha("1/5/1981"), "1981-05-01")
  assert.equal(interpretarFecha("21-05-1981"), "1981-05-21")
  assert.equal(interpretarFecha("1981-05-21"), "1981-05-21")
  assert.equal(interpretarFecha("21 de mayo de 1981"), "1981-05-21")
  assert.equal(interpretarFecha("21 de mayo de 1981."), "1981-05-21")
})

test("rechaza fechas imposibles o ambiguas", () => {
  assert.equal(interpretarFecha("treinta de febrero de mil novecientos ochenta"), null)
  assert.equal(interpretarFecha("29/02/1981"), null)
  assert.equal(interpretarFecha("29/02/1980"), "1980-02-29") // bisiesto
  assert.equal(interpretarFecha("21/05/81"), null) // ¿1881 o 1981?
  assert.equal(interpretarFecha("05/21/1981"), null) // formato estadounidense
  assert.equal(interpretarFecha("mayo de 1981"), null)
  assert.equal(interpretarFecha("hola"), null)
  assert.equal(interpretarFecha("   "), "")
})

test("formato legible", () => {
  assert.equal(formatearFecha("1981-05-21"), "21 de mayo de 1981")
  assert.equal(formatearFecha("1950-01-01"), "1 de enero de 1950")
  assert.equal(formatearFecha(""), "")
})
