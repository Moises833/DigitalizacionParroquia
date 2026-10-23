import { pb } from "./pb.js"

// Las imágenes de los libros son archivos protegidos: su URL necesita un token
// de archivo. Se pide uno al iniciar y se renueva antes de que venza (dura 10 h),
// así las URLs se mantienen estables y el navegador no vuelve a descargar nada.

const RENOVAR_CADA = 8 * 3600 * 1000

export const archivos = $state({ token: "" })
let obtenido = 0
let pidiendo = null

export async function prepararTokenArchivos() {
  if (archivos.token && Date.now() - obtenido < RENOVAR_CADA) return
  pidiendo ??= pb.files.getToken().then((t) => {
    archivos.token = t
    obtenido = Date.now()
  }).finally(() => (pidiendo = null))
  await pidiendo
}

export function urlArchivo(registro, nombre, opciones = {}) {
  if (!nombre) return ""
  return pb.files.getURL(registro, nombre, { ...opciones, token: archivos.token })
}

pb.authStore.onChange(() => {
  archivos.token = ""
  obtenido = 0
})
