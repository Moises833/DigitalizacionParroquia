// Envío de actas a la cola de IA. El recorte se hace aquí, en el navegador; el
// servidor solo lo reenvía a Gemini (ver servidor/pb_hooks/ia.js).

import { pb } from "./pb.js"
import { recortarImagen } from "./recorte.js"

export async function estadoIA() {
  return pb.send("/api/ia/estado", {})
}

export const iaDisponible = (estado) => !!estado?.activo && !!estado?.configurada

/**
 * Envía a la IA los recuadros indicados (posiciones 1..6) de una página.
 * `trabajos`: los que ya existen para la página, para reenviarlos en vez de duplicarlos.
 */
export async function enviarAIA(pagina, urlImagen, segmentos, posiciones, trabajos = []) {
  const enviados = []
  for (const posicion of posiciones) {
    const segmento = segmentos[posicion - 1]
    if (!segmento) continue
    const datos = new FormData()
    datos.append("pagina", pagina.id)
    datos.append("posicion", String(posicion))
    datos.append("recorte", JSON.stringify(segmento))
    datos.append("imagen", await recortarImagen(urlImagen, segmento))
    const existente = trabajos.find((t) => t.posicion === posicion)
    enviados.push(existente
      ? await pb.collection("trabajos_ia").update(existente.id, datos)
      : await pb.collection("trabajos_ia").create(datos))
  }
  return enviados
}

/** Posiciones con recuadro que todavía no tienen acta ni están en la cola. */
export function posicionesSinLeer(segmentos, actas, trabajos) {
  const conActa = new Set(actas.map((a) => a.posicion))
  const enCola = new Set(trabajos.filter((t) => t.estado !== "error").map((t) => t.posicion))
  return segmentos.map((_, i) => i + 1).filter((p) => !conActa.has(p) && !enCola.has(p))
}
