import { test } from "node:test"
import assert from "node:assert/strict"
// La regla vive en el servidor (la usan la página de IA y el instalador).
import modelos from "../../../servidor/pb_hooks/modelos.js"

const { modeloRecomendado, alternativas } = modelos

test("prefiere el flash estable más nuevo", () => {
  assert.equal(modeloRecomendado([
    "gemini-2.0-flash", "gemini-2.5-flash", "gemini-2.5-flash-lite", "gemini-3-flash-preview", "gemini-2.5-pro", "gemini-10-flash",
  ]), "gemini-10-flash")
  assert.equal(modeloRecomendado(["gemini-2.0-flash", "gemini-2.5-flash", "gemini-2.5-pro"]), "gemini-2.5-flash")
})

test("sin flash estable, usa una versión preliminar que no sea lite", () => {
  assert.equal(modeloRecomendado(["gemini-3-flash-preview", "gemini-3-flash-lite-preview", "gemini-3-pro"]), "gemini-3-flash-preview")
})

test("sin ningún flash no sugiere nada", () => {
  assert.equal(modeloRecomendado(["gemini-3-pro", "embedding-001"]), "")
  assert.equal(modeloRecomendado([]), "")
})

test("alternativas: flash estables del más nuevo al más viejo, sin los descartados", () => {
  // Lista real de octubre de 2026 (recortada).
  const lista = ["gemini-2.5-flash", "gemini-flash-latest", "gemini-2.5-flash-lite", "gemini-3-flash-preview",
    "gemini-3.5-flash", "gemini-3.5-flash-lite", "gemini-3.6-flash", "gemini-3.7-flash", "gemini-3.8-flash", "gemini-3.8-flash-tts"]
  assert.deepEqual(alternativas(lista, ["gemini-3.8-flash"]),
    ["gemini-3.7-flash", "gemini-3.6-flash", "gemini-3.5-flash", "gemini-2.5-flash"])
  assert.deepEqual(alternativas(lista), ["gemini-3.8-flash", "gemini-3.7-flash", "gemini-3.6-flash", "gemini-3.5-flash", "gemini-2.5-flash"])
  assert.deepEqual(alternativas(["gemini-3-pro"]), [])
})
