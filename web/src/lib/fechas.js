// Interpretación de fechas tal como aparecen en los libros parroquiales.
// Acepta "1981-05-21", "21/05/1981", "21 de mayo de 1981" y fechas en letras:
// "veintiuno de mayo de mil novecientos ochenta y uno" -> "1981-05-21".
// No depende de ninguna IA: es determinista y funciona sin internet.

export const MESES = [
  "enero", "febrero", "marzo", "abril", "mayo", "junio",
  "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre",
]

const VARIANTES_MES = { setiembre: 9 }

const UNIDADES = {
  cero: 0, un: 1, uno: 1, una: 1, primero: 1, primer: 1, dos: 2, tres: 3, cuatro: 4,
  cinco: 5, seis: 6, siete: 7, ocho: 8, nueve: 9, diez: 10, once: 11, doce: 12,
  trece: 13, catorce: 14, quince: 15, dieciseis: 16, diecisiete: 17, dieciocho: 18,
  diecinueve: 19, veinte: 20, veintiun: 21, veintiuno: 21, veintiuna: 21, veintidos: 22,
  veintitres: 23, veinticuatro: 24, veinticinco: 25, veintiseis: 26, veintisiete: 27,
  veintiocho: 28, veintinueve: 29, treinta: 30, cuarenta: 40, cincuenta: 50,
  sesenta: 60, setenta: 70, ochenta: 80, noventa: 90, cien: 100, ciento: 100,
  doscientos: 200, trescientos: 300, cuatrocientos: 400, quinientos: 500,
  seiscientos: 600, setecientos: 700, ochocientos: 800, novecientos: 900,
}

function normalizar(texto) {
  return String(texto ?? "")
    .normalize("NFD")
    .replace(/[̀-ͯ]/g, "")
    .toLowerCase()
    .replace(/[.,;:]+/g, " ")
    .replace(/\s+/g, " ")
    .trim()
}

/** "mil novecientos ochenta y uno" -> 1981; "21" -> 21; null si no es un número. */
export function numeroEnLetras(texto) {
  const limpio = normalizar(texto)
  if (/^\d+$/.test(limpio)) return parseInt(limpio, 10)
  // "veinte y uno", "diez y seis": forma antigua frecuente en los libros
  const palabras = limpio.split(" ").filter((p) => p && p !== "y")
  if (!palabras.length) return null
  let total = 0
  let actual = 0
  for (const palabra of palabras) {
    if (palabra === "mil") {
      total += (actual || 1) * 1000
      actual = 0
    } else if (palabra in UNIDADES) {
      actual += UNIDADES[palabra]
    } else {
      return null
    }
  }
  return total + actual
}

function mesDesde(texto) {
  const t = normalizar(texto)
  if (/^\d{1,2}$/.test(t)) return parseInt(t, 10)
  const i = MESES.indexOf(t)
  return i >= 0 ? i + 1 : VARIANTES_MES[t] ?? null
}

function aIso(anio, mes, dia) {
  if (!anio || !mes || !dia || anio < 1500 || anio > 2200 || mes < 1 || mes > 12) return null
  const fecha = new Date(Date.UTC(anio, mes - 1, dia))
  if (fecha.getUTCMonth() !== mes - 1 || fecha.getUTCDate() !== dia) return null // 30 de febrero
  return `${String(anio).padStart(4, "0")}-${String(mes).padStart(2, "0")}-${String(dia).padStart(2, "0")}`
}

/**
 * Devuelve "YYYY-MM-DD", "" si el texto está vacío, o null si no se pudo interpretar.
 */
export function interpretarFecha(texto) {
  const t = normalizar(texto)
  if (!t) return ""

  let m = t.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/)
  if (m) return aIso(+m[1], +m[2], +m[3])

  // Día primero, como se escribe en español. Año de 4 dígitos obligatorio:
  // "81" podría ser 1881 o 1981 en un libro antiguo.
  m = t.match(/^(\d{1,2})[/\-\s](\d{1,2})[/\-\s](\d{4})$/)
  if (m) return aIso(+m[3], +m[2], +m[1])

  // "[el] <día> de <mes> de [l año] <año>"
  m = t.match(/^(?:el\s+)?(?:dia\s+)?(.+?)\s+de\s+([a-z]+)\s+(?:de|del)\s+(?:ano\s+)?(.+)$/)
  if (m) {
    const mes = mesDesde(m[2])
    return aIso(numeroEnLetras(m[3]), mes, numeroEnLetras(m[1]))
  }
  return null
}

/** "1981-05-21" -> "21 de mayo de 1981" */
export function formatearFecha(iso) {
  const m = String(iso ?? "").match(/^(\d{4})-(\d{2})-(\d{2})$/)
  if (!m) return ""
  return `${+m[3]} de ${MESES[+m[2] - 1]} de ${m[1]}`
}
