// Textos del estado de los respaldos (página Respaldos y aviso en Inicio).

const HORA = 3600 * 1000

/** "hace 5 minutos", "hace 3 horas", "hace 2 días" */
export function hace(iso, ahora = Date.now()) {
  const t = Date.parse(iso)
  if (!t) return ""
  const minutos = Math.max(0, Math.round((ahora - t) / 60000))
  if (minutos < 1) return "hace un momento"
  if (minutos < 60) return `hace ${minutos} ${minutos === 1 ? "minuto" : "minutos"}`
  const horas = Math.round(minutos / 60)
  if (horas < 48) return `hace ${horas} ${horas === 1 ? "hora" : "horas"}`
  return `hace ${Math.round(horas / 24)} días`
}

/** 1536 -> "1,5 KB" */
export function tamano(bytes) {
  const n = Number(bytes) || 0
  const unidades = ["bytes", "KB", "MB", "GB", "TB"]
  let i = 0
  let v = n
  while (v >= 1024 && i < unidades.length - 1) {
    v /= 1024
    i++
  }
  return `${i === 0 ? v : v.toLocaleString("es", { maximumFractionDigits: 1 })} ${unidades[i]}`
}

/**
 * Aviso para Inicio, o "" si todo está bien. Avisa si no hay ningún destino
 * activo, si nunca se respaldó o si el último respaldo es demasiado viejo.
 */
export function avisoRespaldo(estado, ahora = Date.now()) {
  if (!estado) return ""
  const activos = ["carpeta", "nube"].filter((d) => estado[d]?.activa)
  if (!activos.length) return "Los respaldos están desactivados. Si se daña la computadora, se pierde todo."
  const ultimos = activos.map((d) => Date.parse(estado[d].ultimo_ok) || 0)
  const ultimo = Math.max(...ultimos)
  if (!ultimo) return "Todavía no se hizo ningún respaldo."
  const limite = Math.max(48, 3 * (estado.cada_horas || 24)) * HORA
  if (ahora - ultimo > limite) return `El último respaldo fue ${hace(new Date(ultimo).toISOString(), ahora)}.`
  return ""
}
