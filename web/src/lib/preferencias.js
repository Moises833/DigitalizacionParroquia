// Preferencias de este navegador. Si el almacenamiento local no está disponible
// (modo privado, bloqueado), simplemente no se recuerda nada.

const CLAVE_LIBRO = "libro.actual"
const CLAVE_IMPRESION = "certificado.impresion"

export function impresionPreferida() {
  try { return JSON.parse(localStorage.getItem(CLAVE_IMPRESION) || "{}") } catch { return {} }
}

export function recordarImpresion(opciones) {
  try { localStorage.setItem(CLAVE_IMPRESION, JSON.stringify(opciones)) } catch { /* sin almacenamiento local */ }
}

export function libroPreferido() {
  try { return localStorage.getItem(CLAVE_LIBRO) || "" } catch { return "" }
}

export function recordarLibro(id) {
  try { localStorage.setItem(CLAVE_LIBRO, id) } catch { /* sin almacenamiento local */ }
}
