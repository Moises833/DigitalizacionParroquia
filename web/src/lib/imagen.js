// Compresión de fotos en el navegador antes de subirlas.
// Una foto de teléfono (3–8 MB) queda en ~300–800 KB conservando la letra legible,
// y el servidor de la parroquia no gasta CPU en procesarla.

const LADO_MAXIMO = 2400 // px del lado más largo: suficiente para leer cursiva y para la IA
const CALIDAD = 0.82
const TIPOS_ACEPTADOS = ["image/jpeg", "image/png", "image/webp"]

function canvasABlob(canvas, tipo, calidad) {
  return new Promise((resolver) => canvas.toBlob(resolver, tipo, calidad))
}

/**
 * Devuelve un File comprimido (WebP, o JPEG en navegadores que no codifican WebP).
 * Lanza un Error con mensaje legible si el navegador no puede leer la imagen.
 */
export async function comprimirImagen(archivo) {
  let bitmap
  try {
    // "from-image" aplica la orientación EXIF: las fotos verticales no quedan acostadas.
    bitmap = await createImageBitmap(archivo, { imageOrientation: "from-image" })
  } catch {
    throw new Error(`No se pudo leer «${archivo.name}». Use fotos JPG, PNG o WebP.`)
  }

  const escala = Math.min(1, LADO_MAXIMO / Math.max(bitmap.width, bitmap.height))
  const ancho = Math.round(bitmap.width * escala)
  const alto = Math.round(bitmap.height * escala)

  const canvas = document.createElement("canvas")
  canvas.width = ancho
  canvas.height = alto
  const ctx = canvas.getContext("2d")
  ctx.imageSmoothingQuality = "high"
  ctx.drawImage(bitmap, 0, 0, ancho, alto)
  bitmap.close()

  let blob = await canvasABlob(canvas, "image/webp", CALIDAD)
  // Safari antiguo ignora "image/webp" y devuelve PNG: se usa JPEG en ese caso.
  if (!blob || blob.type !== "image/webp") blob = await canvasABlob(canvas, "image/jpeg", CALIDAD)
  canvas.width = canvas.height = 0 // libera memoria en teléfonos modestos

  // Si la original ya era más liviana y de un tipo aceptado, se conserva.
  if (escala === 1 && TIPOS_ACEPTADOS.includes(archivo.type) && archivo.size <= blob.size) {
    return archivo
  }

  const extension = blob.type === "image/webp" ? "webp" : "jpg"
  const base = archivo.name.replace(/\.[^.]+$/, "") || "pagina"
  return new File([blob], `${base}.${extension}`, { type: blob.type })
}

const ANCHO_MINIATURA = 300

/** Miniatura JPEG liviana (~20 KB) para la galería. */
export async function generarMiniatura(archivo) {
  const bitmap = await createImageBitmap(archivo, { imageOrientation: "from-image" })
  const escala = Math.min(1, ANCHO_MINIATURA / bitmap.width)
  const canvas = document.createElement("canvas")
  canvas.width = Math.round(bitmap.width * escala)
  canvas.height = Math.round(bitmap.height * escala)
  const ctx = canvas.getContext("2d")
  ctx.imageSmoothingQuality = "high"
  ctx.drawImage(bitmap, 0, 0, canvas.width, canvas.height)
  bitmap.close()
  const blob = await canvasABlob(canvas, "image/jpeg", 0.7)
  canvas.width = canvas.height = 0
  return new File([blob], "miniatura.jpg", { type: "image/jpeg" })
}

/** Comprime la foto y genera su miniatura: lo que se sube por cada página. */
export async function prepararPagina(archivo) {
  const imagen = await comprimirImagen(archivo)
  const miniatura = await generarMiniatura(imagen)
  return { imagen, miniatura }
}

export function formatoTamano(bytes) {
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

// Orden natural de folios: "2" < "10" < "10v".
export function compararFolios(a, b) {
  return String(a).localeCompare(String(b), "es", { numeric: true })
}
