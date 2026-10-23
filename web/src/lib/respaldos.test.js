import { test } from "node:test"
import assert from "node:assert/strict"
import { hace, tamano, avisoRespaldo } from "./respaldos.js"

const AHORA = Date.parse("2026-10-22T12:00:00Z")
const antes = (horas) => new Date(AHORA - horas * 3600 * 1000).toISOString()

test("hace cuánto", () => {
  assert.equal(hace(antes(0), AHORA), "hace un momento")
  assert.equal(hace(antes(1 / 60), AHORA), "hace 1 minuto")
  assert.equal(hace(antes(3), AHORA), "hace 3 horas")
  assert.equal(hace(antes(24 * 5), AHORA), "hace 5 días")
  assert.equal(hace("", AHORA), "")
})

test("tamaños", () => {
  assert.equal(tamano(500), "500 bytes")
  assert.equal(tamano(1536), "1,5 KB")
  assert.equal(tamano(5 * 1024 ** 3), "5 GB")
})

test("aviso en Inicio", () => {
  const estado = (carpeta, nube = { activa: false }) => ({ cada_horas: 8, carpeta, nube })
  assert.match(avisoRespaldo(estado({ activa: false }), AHORA), /desactivados/)
  assert.match(avisoRespaldo(estado({ activa: true, ultimo_ok: "" }), AHORA), /ningún respaldo/)
  assert.equal(avisoRespaldo(estado({ activa: true, ultimo_ok: antes(10) }), AHORA), "")
  assert.match(avisoRespaldo(estado({ activa: true, ultimo_ok: antes(24 * 4) }), AHORA), /hace 4 días/)
  // Basta con que uno de los destinos esté al día
  assert.equal(avisoRespaldo(estado({ activa: true, ultimo_ok: antes(24 * 9) }, { activa: true, ultimo_ok: antes(2) }), AHORA), "")
})
