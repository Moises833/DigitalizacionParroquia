// Búsqueda de actas. Se filtra sobre campos normalizados que mantiene el
// servidor (b_nombre, b_padres, b_padrinos): sin acentos y en minúsculas, así
// "jose nunez" encuentra "José Núñez". Cada palabra debe aparecer, en cualquier orden.

const MAX_PALABRAS = 6

export function normalizar(texto) {
  return String(texto ?? "")
    .normalize("NFD")
    .replace(/[̀-ͯ]/g, "")
    .toLowerCase()
    .replace(/\s+/g, " ")
    .trim()
}

export const palabras = (texto) => normalizar(texto).split(" ").filter(Boolean).slice(0, MAX_PALABRAS)

/** "1981" -> [1981, 1981]; "1980-1985" -> [1980, 1985]; "" -> null; inválido -> false */
export function interpretarAnios(texto) {
  const t = String(texto ?? "").trim()
  if (!t) return null
  const m = t.match(/^(\d{4})(?:\s*(?:-|–|a|al|hasta)\s*(\d{4}))?$/i)
  if (!m) return false
  const desde = +m[1]
  const hasta = m[2] ? +m[2] : desde
  return desde <= hasta ? [desde, hasta] : [hasta, desde]
}

export const FILTROS_VACIOS = { nombre: "", padres: "", padrinos: "", libro: "", anio: "", folio: "", numero: "" }

/**
 * Convierte los filtros del formulario en { expresion, parametros } para pb.filter().
 * Devuelve expresion "" si no hay ningún criterio, y error si un dato no es válido.
 */
export function construirFiltro(filtros) {
  const partes = []
  const parametros = {}
  let n = 0
  const param = (valor) => {
    const nombre = `p${n++}`
    parametros[nombre] = valor
    return `{:${nombre}}`
  }

  for (const [campo, columna] of [["nombre", "b_nombre"], ["padres", "b_padres"], ["padrinos", "b_padrinos"]]) {
    for (const palabra of palabras(filtros[campo])) partes.push(`${columna} ~ ${param(palabra)}`)
  }
  if (filtros.libro) partes.push(`libro = ${param(filtros.libro)}`)

  const anios = interpretarAnios(filtros.anio)
  if (anios === false) return { expresion: "", parametros: {}, error: "El año debe ser como 1981 o 1980-1985." }
  if (anios) {
    partes.push(`fecha_bautizo >= ${param(`${anios[0]}-01-01`)}`)
    partes.push(`fecha_bautizo <= ${param(`${anios[1]}-12-31`)}`)
  }
  if (String(filtros.folio ?? "").trim()) partes.push(`pagina.folio = ${param(filtros.folio.trim())}`)
  if (String(filtros.numero ?? "").trim()) partes.push(`numero_acta = ${param(filtros.numero.trim())}`)

  return { expresion: partes.join(" && "), parametros, error: "" }
}

/**
 * Divide un texto en trozos marcando dónde aparecen las palabras buscadas,
 * sin importar acentos: [{ texto, marcado }].
 */
export function resaltar(texto, buscadas) {
  const original = String(texto ?? "")
  if (!original || !buscadas?.length) return [{ texto: original, marcado: false }]
  // Normalizado letra por letra para que cada posición coincida con el original.
  const plano = [...original].map((c) => normalizar(c) || c).join("")
  const marcas = new Array(original.length).fill(false)
  if (plano.length === original.length) {
    for (const palabra of buscadas) {
      let i = plano.indexOf(palabra)
      while (i >= 0) {
        marcas.fill(true, i, i + palabra.length)
        i = plano.indexOf(palabra, i + palabra.length)
      }
    }
  }
  const trozos = []
  for (let i = 0; i < original.length; i++) {
    const ultimo = trozos[trozos.length - 1]
    if (ultimo && ultimo.marcado === marcas[i]) ultimo.texto += original[i]
    else trozos.push({ texto: original[i], marcado: marcas[i] })
  }
  return trozos
}
