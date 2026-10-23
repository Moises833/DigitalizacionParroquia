// Cálculos del informe del piloto que se hacen en el navegador.
import { propuestaADatos, CAMPOS_TEXTO, CAMPOS_FECHA } from "./propuesta.js"

export const ETIQUETAS = {
  numero_acta: "N.º de acta", nombres: "Nombres", apellidos: "Apellidos", sexo: "Sexo",
  fecha_nacimiento: "Fecha de nacimiento", lugar_nacimiento: "Lugar de nacimiento", fecha_bautizo: "Fecha de bautizo",
  padre: "Padre", madre: "Madre", padrino: "Padrino", madrina: "Madrina", ministro: "Ministro",
  registro_civil: "Registro civil", notas_marginales: "Notas marginales", observaciones: "Observaciones",
}
export const CAMPOS_IA = [...CAMPOS_TEXTO, ...CAMPOS_FECHA]

const igual = (a, b) => String(a ?? "").trim().replace(/\s+/g, " ") === String(b ?? "").trim().replace(/\s+/g, " ")
const nuevo = () => ({ propuestos: 0, aceptados: 0, corregidos: 0, agregados: 0 })

/**
 * Compara lo que propuso la IA (ia_respuesta) con lo que quedó guardado en cada
 * acta revisada. Un dato "aceptado" quedó igual; "corregido" lo cambió la
 * persona; "agregado" lo completó la persona porque la IA no lo leyó.
 * También separa los datos que la IA marcó como dudosos (en rojo) de los demás.
 */
export function precisionIA(actas) {
  const campos = Object.fromEntries(CAMPOS_IA.map((c) => [c, nuevo()]))
  const total = nuevo()
  const porMarca = { ia: nuevo(), dudoso: nuevo() }
  let contadas = 0
  for (const acta of actas) {
    if (!acta.ia_respuesta || acta.estado === "borrador") continue
    contadas++
    const { datos, marcas } = propuestaADatos(acta.ia_respuesta)
    for (const c of CAMPOS_IA) {
      const propuesto = datos[c] ?? ""
      const final = acta[c] ?? ""
      const grupos = [campos[c], total]
      if (marcas[c]) grupos.push(porMarca[marcas[c]])
      for (const g of grupos) {
        if (propuesto) {
          g.propuestos++
          if (igual(propuesto, final)) g.aceptados++
          else g.corregidos++
        } else if (String(final).trim()) {
          g.agregados++
        }
      }
    }
  }
  return {
    actas: contadas,
    total,
    porMarca,
    campos: CAMPOS_IA.map((c) => ({ campo: c, etiqueta: ETIQUETAS[c] ?? c, ...campos[c] })),
  }
}

/** 0.873 -> "87 %"; sin datos -> "—" */
export function porcentaje(parte, todo) {
  return todo ? `${Math.round((100 * parte) / todo)} %` : "—"
}

/** 135 -> "2 h 15 min" */
export function duracion(minutos) {
  const m = Math.round(minutos || 0)
  if (m < 60) return `${m} min`
  return `${Math.floor(m / 60)} h${m % 60 ? ` ${m % 60} min` : ""}`
}

/** Actas por hora de trabajo, con un decimal; "—" si hay muy poco tiempo para estimar. */
export function porHora(actas, minutos) {
  if (!actas || minutos < 15) return "—"
  return ((actas * 60) / minutos).toLocaleString("es", { maximumFractionDigits: 1 })
}
