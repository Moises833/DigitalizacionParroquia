import PocketBase from "pocketbase"

// La app siempre se sirve desde el mismo origen que la API (PocketBase o el proxy de Vite).
export const pb = new PocketBase("/")

// El SDK cancela por defecto las peticiones simultáneas a una misma colección.
// Se desactiva: donde haga falta (búsqueda reactiva) se usará `requestKey` explícito.
pb.autoCancellation(false)

// Mensaje legible a partir de un error del SDK de PocketBase.
export function mensajeError(err) {
  const campos = err?.response?.data
  if (campos && typeof campos === "object") {
    const primero = Object.entries(campos)[0]
    if (primero) return `${primero[0]}: ${primero[1]?.message ?? "valor inválido"}`
  }
  if (err?.status === 0) return "No hay conexión con el servidor."
  return err?.response?.message || err?.message || "Ocurrió un error inesperado."
}
