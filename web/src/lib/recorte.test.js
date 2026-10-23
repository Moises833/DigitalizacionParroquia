import { test } from "node:test"
import assert from "node:assert/strict"
import { franjas, ajustar, arrastrar, TAMANO_MINIMO } from "./recorte.js"

const dentro = (s) => s.x >= 0 && s.y >= 0 && s.x + s.ancho <= 1.00001 && s.y + s.alto <= 1.00001

test("tres franjas sin solaparse y dentro de la imagen", () => {
  const f = franjas(3)
  assert.equal(f.length, 3)
  for (const s of f) assert.ok(dentro(s))
  assert.ok(f[0].y + f[0].alto <= f[1].y)
  assert.ok(f[1].y + f[1].alto <= f[2].y)
})

test("mover no saca el recuadro de la imagen", () => {
  const s = { x: 0.1, y: 0.1, ancho: 0.5, alto: 0.3 }
  assert.deepEqual(arrastrar(s, "mover", 0.9, 0.9), { x: 0.5, y: 0.7, ancho: 0.5, alto: 0.3 })
  assert.deepEqual(arrastrar(s, "mover", -1, -1), { x: 0, y: 0, ancho: 0.5, alto: 0.3 })
})

test("estirar el borde inferior y el superior", () => {
  const s = { x: 0.1, y: 0.2, ancho: 0.5, alto: 0.3 }
  assert.deepEqual(arrastrar(s, "s", 0, 0.1), { x: 0.1, y: 0.2, ancho: 0.5, alto: 0.4 })
  const n = arrastrar(s, "n", 0, -0.1)
  assert.equal(n.y, 0.1)
  assert.equal(n.alto, 0.4) // el borde de abajo queda fijo
})

test("no se puede achicar por debajo del mínimo ni invertir", () => {
  const s = { x: 0.1, y: 0.2, ancho: 0.5, alto: 0.3 }
  const r = arrastrar(s, "n", 0, 0.9)
  assert.ok(r.alto >= TAMANO_MINIMO - 1e-9)
  assert.ok(Math.abs(r.y + r.alto - 0.5) < 1e-4)
  const e = arrastrar(s, "se", 2, 2)
  assert.ok(dentro(e))
})

test("ajustar corrige valores fuera de rango", () => {
  assert.ok(dentro(ajustar({ x: 0.9, y: 0.9, ancho: 0.5, alto: 0.5 })))
})
