// Elección del modelo de Gemini entre los que ofrece la clave. Google retira
// modelos viejos (a veces los sigue listando pero responde 404 a claves nuevas)
// y los más nuevos suelen estar saturados: se prefiere el «flash» estable más
// nuevo (sin «lite», «preview» ni versiones experimentales) y, si no responde,
// los anteriores.
// JavaScript puro: lo usan ia.js (con require) y las pruebas de web/ (con Node).

function version(nombre) {
  const m = /^gemini-(\d+(?:\.\d+)?)/.exec(nombre)
  return m ? parseFloat(m[1]) : 0
}

const masNuevoPrimero = (lista) => lista.slice().sort((a, b) => version(b) - version(a))

/** Los «flash» estables, del más nuevo al más viejo, sin los `descartados`. */
function alternativas(modelos, descartados = []) {
  return masNuevoPrimero(modelos.filter((m) => /^gemini-\d+(\.\d+)?-flash$/.test(m) && !descartados.includes(m)))
}

function modeloRecomendado(modelos) {
  return alternativas(modelos)[0] ||
    masNuevoPrimero(modelos.filter((m) => /^gemini-.*flash/.test(m) && !/lite/.test(m)))[0] || ""
}

module.exports = { modeloRecomendado, alternativas }
