import { test } from "node:test"
import assert from "node:assert/strict"
import {
  numeroALetras, expedicionEnLetras, unirPersonas, nombreCompleto, laParroquia, referenciaLibro, etiquetaLibro, paraFines,
} from "./certificado.js"

test("números en letras", () => {
  assert.equal(numeroALetras(1), "uno")
  assert.equal(numeroALetras(16), "dieciséis")
  assert.equal(numeroALetras(22), "veintidós")
  assert.equal(numeroALetras(31), "treinta y uno")
  assert.equal(numeroALetras(100), "cien")
  assert.equal(numeroALetras(1981), "mil novecientos ochenta y uno")
  assert.equal(numeroALetras(1900), "mil novecientos")
  assert.equal(numeroALetras(2000), "dos mil")
  assert.equal(numeroALetras(2026), "dos mil veintiséis")
  assert.equal(numeroALetras(1515), "mil quinientos quince")
})

test("fecha de expedición en letras", () => {
  assert.equal(expedicionEnLetras("2026-10-22"), "a los veintidós días del mes de octubre de dos mil veintiséis")
  assert.equal(expedicionEnLetras("2026-03-01"), "al primer día del mes de marzo de dos mil veintiséis")
  assert.equal(expedicionEnLetras("2026-05-21"), "a los veintiún días del mes de mayo de dos mil veintiséis")
  assert.equal(expedicionEnLetras("2026-12-31"), "a los treinta y un días del mes de diciembre de dos mil veintiséis")
  assert.equal(expedicionEnLetras(""), "")
})

test("personas y nombre", () => {
  assert.equal(unirPersonas("Juan", "Ana"), "Juan y Ana")
  assert.equal(unirPersonas("", " Ana "), "Ana")
  assert.equal(unirPersonas("", ""), "")
  assert.equal(nombreCompleto({ nombres: "Rosa", apellidos: "Pérez" }), "Rosa Pérez")
})

test("frases del certificado", () => {
  assert.equal(laParroquia("Parroquia San José"), "la Parroquia San José")
  assert.equal(laParroquia("San José"), "la parroquia San José")
  assert.equal(referenciaLibro("12"), "N.º 12")
  assert.equal(referenciaLibro("Tomo III"), "Tomo III")
  assert.equal(etiquetaLibro("12"), "Libro N.º 12")
  assert.equal(etiquetaLibro("Libro 3"), "Libro 3")
  assert.equal(etiquetaLibro("Bautismos A"), "Libro Bautismos A")
  assert.equal(paraFines(" Primera comunión "), ", para fines de primera comunión")
  assert.equal(paraFines(""), "")
})
