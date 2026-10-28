import { test } from "node:test"
import assert from "node:assert/strict"
// La regla vive en el servidor (la usan la página de IA y el instalador).
import modelos from "../../../servidor/pb_hooks/modelos.js"

const { modeloRecomendado, modeloAUsar } = modelos

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

test("conserva el modelo configurado si sigue disponible", () => {
  assert.equal(modeloAUsar("gemini-2.0-flash", ["gemini-2.0-flash", "gemini-2.5-flash"]), "gemini-2.0-flash")
  assert.equal(modeloAUsar("gemini-1.5-flash", ["gemini-2.0-flash", "gemini-2.5-flash"]), "gemini-2.5-flash")
  assert.equal(modeloAUsar("gemini-1.5-flash", ["gemini-3-pro"]), "gemini-1.5-flash")
})
