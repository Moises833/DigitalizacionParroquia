// Enrutador mínimo basado en el hash (#/libros), sin dependencias.
export const ruta = $state({ actual: leer() })

function leer() {
  return window.location.hash.replace(/^#\/?/, "") || "inicio"
}

window.addEventListener("hashchange", () => {
  ruta.actual = leer()
})

export function ir(destino) {
  window.location.hash = `/${destino}`
}
