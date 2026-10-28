// Elección del modelo de Gemini entre los que ofrece la clave. Google retira
// modelos viejos: si el configurado ya no está, se usa el «flash» estable más
// nuevo (sin «lite», «preview» ni versiones experimentales).
// JavaScript puro: lo usan ia.js (con require) y las pruebas de web/ (con Node).

function version(nombre) {
  const m = /^gemini-(\d+(?:\.\d+)?)/.exec(nombre)
  return m ? parseFloat(m[1]) : 0
}

function modeloRecomendado(modelos) {
  const estables = modelos.filter((m) => /^gemini-\d+(\.\d+)?-flash$/.test(m))
  const candidatos = estables.length ? estables : modelos.filter((m) => /^gemini-.*flash/.test(m) && !/lite/.test(m))
  return candidatos.slice().sort((a, b) => version(b) - version(a))[0] || ""
}

/** El modelo a usar: el configurado si sigue disponible, si no el recomendado. */
function modeloAUsar(actual, modelos) {
  return modelos.includes(actual) ? actual : modeloRecomendado(modelos) || actual
}

module.exports = { modeloRecomendado, modeloAUsar }
