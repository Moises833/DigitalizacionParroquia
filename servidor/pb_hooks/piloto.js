// Informe del piloto y exportación de actas. Se carga con require() en cada handler.

const PAUSA = 20 * 60 * 1000 // más de 20 minutos entre dos actas = otra sesión de trabajo
const PRIMERA = 3 * 60 * 1000 // tiempo que se le cuenta a la primera acta de cada sesión
const DIAS = 60

function filas(app, sql, modelo, parametros = {}) {
  const lista = arrayOf(new DynamicModel(modelo))
  app.db().newQuery(sql).bind(parametros).all(lista)
  return lista.map((f) => Object.fromEntries(Object.keys(modelo).map((k) => [k, f[k]])))
}

// "2026-10-22 14:05:09.123Z" -> milisegundos
const instante = (texto) => Date.parse(String(texto).replace(" ", "T"))

const dos = (n) => String(n).padStart(2, "0")

function diaLocal(ms) {
  const d = new Date(ms)
  return `${d.getFullYear()}-${dos(d.getMonth() + 1)}-${dos(d.getDate())}`
}

// Fecha y hora de la computadora (la base guarda en UTC), para el CSV.
function horaLocal(texto) {
  const ms = instante(texto)
  if (isNaN(ms)) return ""
  const d = new Date(ms)
  return `${diaLocal(ms)} ${dos(d.getHours())}:${dos(d.getMinutes())}`
}

/** Minutos de trabajo estimados a partir de cuándo se guardó cada acta. */
function minutosActivos(instantes) {
  let total = 0
  instantes.forEach((t, i) => {
    const pausa = i ? t - instantes[i - 1] : Infinity
    total += pausa <= PAUSA ? pausa : PRIMERA
  })
  return total / 60000
}

function tamanoCarpeta(ruta) {
  let total = 0
  let entradas
  try {
    entradas = $os.readDir(ruta)
  } catch {
    return 0
  }
  for (const e of entradas) {
    total += e.isDir() ? tamanoCarpeta($filepath.join(ruta, e.name())) : e.info().size()
  }
  return total
}

function informe(app) {
  const libros = filas(app, `
    SELECT l.id, l.numero,
      (SELECT COUNT(*) FROM paginas p WHERE p.libro = l.id) AS paginas,
      (SELECT COUNT(*) FROM paginas p WHERE p.libro = l.id AND p.estado = 'pendiente') AS sin_separar,
      (SELECT COUNT(*) FROM paginas p WHERE p.libro = l.id AND p.estado = 'completa') AS completas,
      (SELECT COUNT(*) FROM actas_bautismo a WHERE a.libro = l.id) AS actas,
      (SELECT COUNT(*) FROM actas_bautismo a WHERE a.libro = l.id AND a.estado = 'borrador') AS borradores,
      (SELECT COUNT(*) FROM actas_bautismo a WHERE a.libro = l.id AND a.origen_datos = 'ia') AS con_ia,
      (SELECT COUNT(*) FROM certificados c JOIN actas_bautismo a ON a.id = c.acta WHERE a.libro = l.id) AS certificados
    FROM libros l ORDER BY l.anio_inicio, l.numero`,
  { id: "", numero: "", paginas: 0, sin_separar: 0, completas: 0, actas: 0, borradores: 0, con_ia: 0, certificados: 0 })

  const nombres = {}
  for (const u of filas(app, "SELECT id, name, email FROM users", { id: "", name: "", email: "" })) {
    nombres[u.id] = u.name || u.email
  }

  // Ritmo por persona y actividad por día (últimos DIAS días)
  const desde = new Date(Date.now() - DIAS * 86400000).toISOString().replace("T", " ")
  const actas = filas(app,
    "SELECT creado_por, created FROM actas_bautismo WHERE created >= {:desde} ORDER BY creado_por, created",
    { creado_por: "", created: "" }, { desde })
  const paginas = filas(app, "SELECT created FROM paginas WHERE created >= {:desde}", { created: "" }, { desde })
  const revisadas = filas(app,
    "SELECT revisado_por AS usuario, COUNT(*) AS n FROM actas_bautismo WHERE revisado_por != '' AND updated >= {:desde} GROUP BY revisado_por",
    { usuario: "", n: 0 }, { desde })

  const porUsuario = {}
  const porDia = {}
  const dia = (ms) => {
    const clave = diaLocal(ms)
    if (!porDia[clave]) porDia[clave] = { actas: 0, paginas: 0 }
    return porDia[clave]
  }
  for (const a of actas) {
    const t = instante(a.created)
    if (!porUsuario[a.creado_por]) porUsuario[a.creado_por] = []
    porUsuario[a.creado_por].push(t)
    dia(t).actas++
  }
  for (const p of paginas) dia(instante(p.created)).paginas++

  const personas = {}
  const persona = (id) => {
    if (!personas[id]) personas[id] = { nombre: nombres[id] ?? "(sin usuario)", actas: 0, revisadas: 0, minutos: 0 }
    return personas[id]
  }
  for (const [id, instantes] of Object.entries(porUsuario)) {
    persona(id).actas = instantes.length
    persona(id).minutos = Math.round(minutosActivos(instantes))
  }
  for (const r of revisadas) persona(r.usuario).revisadas = r.n

  const ia = {}
  for (const f of filas(app, "SELECT estado, COUNT(*) AS n FROM trabajos_ia GROUP BY estado", { estado: "", n: 0 })) {
    ia[f.estado] = f.n
  }

  const datos = $filepath.isAbs(app.dataDir()) ? app.dataDir() : $filepath.join($os.getwd(), app.dataDir())
  let base = 0
  try { base = $os.stat($filepath.join(datos, "data.db")).size() } catch { /* sin base */ }

  return {
    dias: DIAS,
    libros,
    personas: Object.values(personas).sort((a, b) => b.actas - a.actas),
    por_dia: Object.entries(porDia).sort(([a], [b]) => (a < b ? 1 : -1)).map(([dia, v]) => ({ dia, ...v })),
    ia,
    certificados: filas(app, "SELECT COUNT(*) AS n FROM certificados", { n: 0 })[0].n,
    observaciones_pendientes: filas(app, "SELECT COUNT(*) AS n FROM observaciones WHERE estado = 'pendiente'", { n: 0 })[0].n,
    espacio: { base, imagenes: tamanoCarpeta($filepath.join(datos, "storage")) },
  }
}

// --- Exportar las actas de un libro a CSV (Excel) -----------------------------

const COLUMNAS = [
  ["Libro", "libro"], ["Folio", "folio"], ["Posición", "posicion"], ["N.º acta", "numero_acta"],
  ["Nombres", "nombres"], ["Apellidos", "apellidos"], ["Sexo", "sexo"],
  ["Fecha de nacimiento", "fecha_nacimiento"], ["Lugar de nacimiento", "lugar_nacimiento"],
  ["Fecha de bautizo", "fecha_bautizo"], ["Padre", "padre"], ["Madre", "madre"],
  ["Padrino", "padrino"], ["Madrina", "madrina"], ["Ministro", "ministro"],
  ["Registro civil", "registro_civil"], ["Notas marginales", "notas_marginales"], ["Observaciones", "observaciones"],
  ["Estado", "estado"], ["Origen", "origen_datos"], ["Creada por", "creado"], ["Revisada por", "revisado"],
  ["Creada", "created"], ["Modificada", "updated"],
]

// Excel en español separa con punto y coma. Un valor que empieza con = + - @
// se antepone con ' para que Excel no lo ejecute como fórmula.
function celda(valor) {
  let t = String(valor ?? "")
  if (/^[=+\-@\t\r]/.test(t)) t = `'${t}`
  return /[";\r\n]/.test(t) ? `"${t.replace(/"/g, '""')}"` : t
}

function csvActas(app, libroId) {
  const libro = app.findRecordById("libros", libroId)
  const modelo = Object.fromEntries(COLUMNAS.map(([, c]) => [c, c === "posicion" ? 0 : ""]))
  const lista = filas(app, `
    SELECT l.numero AS libro, COALESCE(p.folio, '') AS folio, COALESCE(a.posicion, 0) AS posicion,
      a.numero_acta, a.nombres, a.apellidos, a.sexo, a.fecha_nacimiento, a.lugar_nacimiento, a.fecha_bautizo,
      a.padre, a.madre, a.padrino, a.madrina, a.ministro, a.registro_civil, a.notas_marginales, a.observaciones,
      a.estado, a.origen_datos,
      COALESCE(NULLIF(uc.name, ''), uc.email, '') AS creado, COALESCE(NULLIF(ur.name, ''), ur.email, '') AS revisado,
      a.created, a.updated
    FROM actas_bautismo a
    JOIN libros l ON l.id = a.libro
    LEFT JOIN paginas p ON p.id = a.pagina
    LEFT JOIN users uc ON uc.id = a.creado_por
    LEFT JOIN users ur ON ur.id = a.revisado_por
    WHERE a.libro = {:libro}
    ORDER BY CAST(p.folio AS INTEGER), p.folio, a.posicion, a.created`, modelo, { libro: libroId })
  const lineas = [COLUMNAS.map(([titulo]) => celda(titulo)).join(";")]
  for (const f of lista) {
    f.created = horaLocal(f.created)
    f.updated = horaLocal(f.updated)
    lineas.push(COLUMNAS.map(([, c]) => celda(f[c])).join(";"))
  }
  return { nombre: libro.getString("numero"), csv: "﻿" + lineas.join("\r\n") + "\r\n", total: lista.length }
}

module.exports = { informe, csvActas, minutosActivos, celda }
