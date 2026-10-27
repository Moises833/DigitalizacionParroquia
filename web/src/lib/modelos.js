// Elección del modelo de Gemini entre los que ofrece la clave. Google retira
// modelos viejos: si el configurado ya no está, se sugiere el «flash» estable
// más nuevo (sin «lite», «preview» ni versiones experimentales).

const version = (nombre) => (nombre.match(/^gemini-(\d+(?:\.\d+)?)/) || [])[1]

export function modeloRecomendado(modelos) {
  const estables = modelos.filter((m) => /^gemini-\d+(\.\d+)?-flash$/.test(m))
  const candidatos = estables.length ? estables : modelos.filter((m) => /^gemini-.*flash/.test(m) && !/lite/.test(m))
  return candidatos.sort((a, b) => parseFloat(version(b) || 0) - parseFloat(version(a) || 0))[0] || ""
}
