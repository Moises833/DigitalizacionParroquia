// Recuadros de actas dentro de una página. Coordenadas en proporciones 0..1
// respecto de la imagen, así no dependen de la resolución de la foto.

export const MAX_SEGMENTOS = 6
export const TAMANO_MINIMO = 0.03

const MARGEN = 0.01

/** N franjas horizontales iguales: el caso típico de 3 actas por página. */
export function franjas(cantidad) {
  return Array.from({ length: cantidad }, (_, i) => ({
    x: 0.02,
    y: i / cantidad + MARGEN / 2,
    ancho: 0.96,
    alto: 1 / cantidad - MARGEN,
  }))
}

const acotar = (v, min, max) => Math.min(max, Math.max(min, v))
const redondear = (v) => Math.round(v * 10000) / 10000

/** Mantiene el recuadro dentro de la imagen y con un tamaño mínimo. */
export function ajustar({ x, y, ancho, alto }) {
  ancho = acotar(ancho, TAMANO_MINIMO, 1)
  alto = acotar(alto, TAMANO_MINIMO, 1)
  return {
    x: redondear(acotar(x, 0, 1 - ancho)),
    y: redondear(acotar(y, 0, 1 - alto)),
    ancho: redondear(ancho),
    alto: redondear(alto),
  }
}

/**
 * Aplica un arrastre (dx, dy en proporciones) según la parte tomada:
 * "mover" o una combinación de bordes "n", "s", "e", "o" (p. ej. "se").
 */
export function arrastrar(inicial, parte, dx, dy) {
  if (parte === "mover") return ajustar({ ...inicial, x: inicial.x + dx, y: inicial.y + dy })
  let { x, y, ancho, alto } = inicial
  const derecha = x + ancho
  const abajo = y + alto
  if (parte.includes("n")) y = acotar(y + dy, 0, abajo - TAMANO_MINIMO)
  if (parte.includes("s")) alto = acotar(alto + dy, TAMANO_MINIMO, 1 - y)
  if (parte.includes("o")) x = acotar(x + dx, 0, derecha - TAMANO_MINIMO)
  if (parte.includes("e")) ancho = acotar(ancho + dx, TAMANO_MINIMO, 1 - x)
  if (parte.includes("n")) alto = abajo - y
  if (parte.includes("o")) ancho = derecha - x
  return ajustar({ x, y, ancho, alto })
}

/** ¿El recorte guardado en el acta sigue coincidiendo con el recuadro? */
export function mismoRecorte(a, b) {
  return !!a && !!b && ["x", "y", "ancho", "alto"].every((k) => Math.abs((a[k] ?? -1) - b[k]) < 1e-6)
}

function cargarImagen(url) {
  return new Promise((resolver, rechazar) => {
    const img = new Image()
    img.onload = () => resolver(img)
    img.onerror = () => rechazar(new Error("No se pudo cargar la imagen de la página."))
    img.src = url
  })
}

const ANCHO_MAXIMO_RECORTE = 1600

/** Recorta la región del acta de la imagen completa (para la IA y la fe de bautismo). */
export async function recortarImagen(url, segmento) {
  const img = await cargarImagen(url)
  const sx = segmento.x * img.naturalWidth
  const sy = segmento.y * img.naturalHeight
  const sw = segmento.ancho * img.naturalWidth
  const sh = segmento.alto * img.naturalHeight
  const escala = Math.min(1, ANCHO_MAXIMO_RECORTE / sw)

  const canvas = document.createElement("canvas")
  canvas.width = Math.round(sw * escala)
  canvas.height = Math.round(sh * escala)
  const ctx = canvas.getContext("2d")
  ctx.imageSmoothingQuality = "high"
  ctx.drawImage(img, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height)

  let blob = await new Promise((r) => canvas.toBlob(r, "image/webp", 0.85))
  if (!blob || blob.type !== "image/webp") blob = await new Promise((r) => canvas.toBlob(r, "image/jpeg", 0.85))
  canvas.width = canvas.height = 0
  const extension = blob.type === "image/webp" ? "webp" : "jpg"
  return new File([blob], `recorte.${extension}`, { type: blob.type })
}
