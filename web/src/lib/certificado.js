// Textos de la fe de bautismo. Las fechas del acta se escriben "21 de mayo de 1981";
// la de expedición, como es costumbre, en letras.
import { MESES } from "./fechas.js"

export const PAPELES = {
  carta: { texto: "Carta (216 × 279 mm)", ancho: 216, alto: 279 },
  a4: { texto: "A4 (210 × 297 mm)", ancho: 210, alto: 297 },
  oficio: { texto: "Oficio (216 × 330 mm)", ancho: 216, alto: 330 },
}

export const CARGOS = ["Párroco", "Vicario parroquial", "Administrador parroquial", "Secretaria parroquial"]
export const FINES = ["Primera comunión", "Confirmación", "Matrimonio", "Padrino o madrina", "Trámites personales"]

const UNIDADES = ["", "uno", "dos", "tres", "cuatro", "cinco", "seis", "siete", "ocho", "nueve",
  "diez", "once", "doce", "trece", "catorce", "quince", "dieciséis", "diecisiete", "dieciocho", "diecinueve",
  "veinte", "veintiuno", "veintidós", "veintitrés", "veinticuatro", "veinticinco", "veintiséis",
  "veintisiete", "veintiocho", "veintinueve"]
const DECENAS = ["", "", "", "treinta", "cuarenta", "cincuenta", "sesenta", "setenta", "ochenta", "noventa"]
const CENTENAS = ["", "ciento", "doscientos", "trescientos", "cuatrocientos", "quinientos",
  "seiscientos", "setecientos", "ochocientos", "novecientos"]

function hasta100(n) {
  if (n < 30) return UNIDADES[n]
  const d = Math.floor(n / 10)
  const u = n % 10
  return u ? `${DECENAS[d]} y ${UNIDADES[u]}` : DECENAS[d]
}

function hasta1000(n) {
  if (n === 100) return "cien"
  const c = Math.floor(n / 100)
  const resto = n % 100
  return [CENTENAS[c], resto ? hasta100(resto) : ""].filter(Boolean).join(" ")
}

/** 1981 -> "mil novecientos ochenta y uno" (de 1 a 9999). */
export function numeroALetras(n) {
  if (!Number.isInteger(n) || n < 1 || n > 9999) return String(n)
  const miles = Math.floor(n / 1000)
  const resto = n % 1000
  const partes = []
  if (miles) partes.push(miles === 1 ? "mil" : `${UNIDADES[miles]} mil`)
  if (resto) partes.push(hasta1000(resto))
  return partes.join(" ")
}

/** "2026-10-22" -> "a los veintidós días del mes de octubre de dos mil veintiséis" */
export function expedicionEnLetras(iso) {
  const m = String(iso ?? "").match(/^(\d{4})-(\d{2})-(\d{2})$/)
  if (!m) return ""
  const dia = +m[3]
  const mes = MESES[+m[2] - 1]
  const anio = numeroALetras(+m[1])
  if (dia === 1) return `al primer día del mes de ${mes} de ${anio}`
  // "veintiún días", "treinta y un días"
  const dias = numeroALetras(dia).replace(/uno$/, "ún").replace(/ y ún$/, " y un")
  return `a los ${dias} días del mes de ${mes} de ${anio}`
}

export const unirPersonas = (a, b) => [a, b].map((x) => String(x ?? "").trim()).filter(Boolean).join(" y ")

export function nombreCompleto(datos) {
  return [datos?.nombres, datos?.apellidos].map((x) => String(x ?? "").trim()).filter(Boolean).join(" ")
}

/** "San José" -> "la parroquia San José"; "Parroquia San José" -> "la Parroquia San José" */
export function laParroquia(nombre) {
  const n = String(nombre ?? "").trim()
  return /^parroquia\b/i.test(n) ? `la ${n}` : `la parroquia ${n}`
}

/** "12" -> "N.º 12"; "Tomo III" queda igual. */
export function referenciaLibro(libro) {
  const l = String(libro ?? "").trim()
  return /^\d+\s*[a-z]?$/i.test(l) ? `N.º ${l}` : l
}

/** Para la línea de referencia: "12" -> "Libro N.º 12"; "Tomo III" y "Libro 3" quedan igual. */
export function etiquetaLibro(libro) {
  const l = String(libro ?? "").trim()
  if (/^\d+\s*[a-z]?$/i.test(l)) return `Libro N.º ${l}`
  return /^(libro|tomo)\b/i.test(l) ? l : `Libro ${l}`
}

/** "Matrimonio" -> ", para fines de matrimonio" */
export function paraFines(fines) {
  const f = String(fines ?? "").trim()
  return f ? `, para fines de ${f[0].toLowerCase()}${f.slice(1)}` : ""
}

/** Fecha local de hoy como "YYYY-MM-DD". */
export function hoy() {
  const d = new Date()
  const dos = (n) => String(n).padStart(2, "0")
  return `${d.getFullYear()}-${dos(d.getMonth() + 1)}-${dos(d.getDate())}`
}
