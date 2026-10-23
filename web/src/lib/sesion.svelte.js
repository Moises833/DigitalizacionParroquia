import { pb } from "./pb.js"

// Estado reactivo de la sesión, sincronizado con el authStore de PocketBase.
export const sesion = $state({
  usuario: pb.authStore.isValid ? pb.authStore.record : null,
})

pb.authStore.onChange(() => {
  sesion.usuario = pb.authStore.isValid ? pb.authStore.record : null
})

export const esAdmin = () => sesion.usuario?.rol === "admin"

export async function iniciarSesion(email, password) {
  await pb.collection("users").authWithPassword(email, password)
}

export function cerrarSesion() {
  pb.authStore.clear()
}

// Renueva el token al abrir la app; si expiró o el usuario fue borrado, cierra la sesión.
export async function renovarSesion() {
  if (!pb.authStore.isValid) return
  try {
    await pb.collection("users").authRefresh()
  } catch {
    pb.authStore.clear()
  }
}
