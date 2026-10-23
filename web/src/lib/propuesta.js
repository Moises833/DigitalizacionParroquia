// Convierte la propuesta de la IA (trabajos_ia.resultado) en datos del formulario
// del acta, marcando cada campo para que la persona lo verifique.

import { interpretarFecha } from "./fechas.js"

export const CAMPOS_TEXTO = [
  "numero_acta", "nombres", "apellidos", "sexo", "lugar_nacimiento", "padre", "madre",
  "padrino", "madrina", "ministro", "registro_civil", "observaciones", "notas_marginales",
]
export const CAMPOS_FECHA = ["fecha_nacimiento", "fecha_bautizo"]

/**
 * Devuelve { datos, marcas } donde marcas[campo] es "ia" (leído por la IA) o
 * "dudoso" (la IA no está segura, o su fecha no coincide con el texto que leyó).
 */
export function propuestaADatos(resultado) {
  const datos = {}
  const marcas = {}
  if (!resultado) return { datos, marcas }
  const dudosos = new Set(resultado.campos_dudosos ?? [])

  for (const campo of CAMPOS_TEXTO) {
    const valor = String(resultado[campo] ?? "").trim()
    if (valor) {
      datos[campo] = valor
      marcas[campo] = dudosos.has(campo) ? "dudoso" : "ia"
    } else if (dudosos.has(campo)) {
      marcas[campo] = "dudoso"
    }
  }
  if (datos.sexo && datos.sexo !== "M" && datos.sexo !== "F") {
    delete datos.sexo
    marcas.sexo = "dudoso"
  }

  // La fecha se vuelve a interpretar desde el texto leído, sin depender de la
  // conversión de la IA. Si ambas no coinciden, se marca para revisar.
  for (const campo of CAMPOS_FECHA) {
    const iso = interpretarFecha(resultado[campo] ?? "")
    const desdeTexto = interpretarFecha(resultado[`${campo}_texto`] ?? "")
    const valor = desdeTexto || iso || ""
    if (valor) datos[campo] = valor
    const discrepan = iso && desdeTexto && iso !== desdeTexto
    if (dudosos.has(campo) || discrepan || (!valor && resultado[`${campo}_texto`])) marcas[campo] = "dudoso"
    else if (valor) marcas[campo] = "ia"
  }
  return { datos, marcas }
}
