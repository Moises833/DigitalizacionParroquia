// Preferencias de este navegador. Si el almacenamiento local no está disponible
// (modo privado, bloqueado), simplemente no se recuerda nada.

const CLAVE_LIBRO = "libro.actual"

export function libroPreferido() {
  try { return localStorage.getItem(CLAVE_LIBRO) || "" } catch { return "" }
}

export function recordarLibro(id) {
  try { localStorage.setItem(CLAVE_LIBRO, id) } catch { /* sin almacenamiento local */ }
}
