"""Pruebas de las herramientas del piloto (Fase 8): observaciones, informe y exportación.

Uso: después de prueba_api.py (usa sus usuarios). Crea su propio libro y actas.
"""
import csv
import datetime
import io
import json
import struct
import urllib.error
import urllib.request
import uuid
import zlib

B = "http://127.0.0.1:8090/api"
fallas = 0


def req(method, path, body=None, token=None, crudo=False):
    data = json.dumps(body).encode("utf-8") if body is not None else None
    r = urllib.request.Request(B + path, data=data, method=method)
    r.add_header("Content-Type", "application/json")
    if token:
        r.add_header("Authorization", token)
    try:
        with urllib.request.urlopen(r) as resp:
            cuerpo = resp.read()
            return resp.status, (cuerpo, dict(resp.headers)) if crudo else json.loads(cuerpo or b"{}")
    except urllib.error.HTTPError as e:
        return e.code, json.loads(e.read() or b"{}")


def subir_pagina(libro, folio, token):
    fila = b"\x00" + b"\xee\xdd\xcc" * 20
    def trozo(tipo, datos):
        return struct.pack(">I", len(datos)) + tipo + datos + struct.pack(">I", zlib.crc32(tipo + datos) & 0xFFFFFFFF)
    png = (b"\x89PNG\r\n\x1a\n" + trozo(b"IHDR", struct.pack(">IIBBBBB", 20, 20, 8, 2, 0, 0, 0))
           + trozo(b"IDAT", zlib.compress(fila * 20)) + trozo(b"IEND", b""))
    limite = uuid.uuid4().hex
    partes = [f'--{limite}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode()
              for k, v in {"libro": libro, "folio": folio, "estado": "pendiente", "origen": "pc"}.items()]
    partes.append(f'--{limite}\r\nContent-Disposition: form-data; name="imagen"; filename="p.png"\r\n'
                  f'Content-Type: image/png\r\n\r\n'.encode() + png + b"\r\n" + f"--{limite}--\r\n".encode())
    r = urllib.request.Request(B + "/collections/paginas/records", data=b"".join(partes), method="POST")
    r.add_header("Content-Type", f"multipart/form-data; boundary={limite}")
    r.add_header("Authorization", token)
    with urllib.request.urlopen(r) as resp:
        return json.loads(resp.read())["id"]


def check(nombre, cond, extra=""):
    global fallas
    fallas += 0 if cond else 1
    print(("OK   " if cond else "FALLA") + " " + nombre + (" -> " + str(extra)[:400] if not cond else ""))


def login(email):
    return req("POST", "/collections/users/auth-with-password", {"identity": email, "password": "clave-prueba-123"})


s, a = login("admin@prueba.test")
admin, admin_id = a["token"], a["record"]["id"]
s, a = login("sec@prueba.test")
sec, sec_id, sec_nombre = a["token"], a["record"]["id"], a["record"]["name"]

# --- Contraseña olvidada --------------------------------------------------------------
correo = f"olvido-{uuid.uuid4().hex[:6]}@prueba.test"
s, u = req("POST", "/collections/users/records", {"email": correo, "password": "clave-vieja-123",
           "passwordConfirm": "clave-vieja-123", "rol": "secretaria", "name": "Olvidadiza"}, admin)
s, b = req("PATCH", f"/collections/users/records/{u['id']}", {"password": "clave-nueva-123", "passwordConfirm": "clave-nueva-123"}, admin)
check("el administrador pone una contraseña nueva sin saber la anterior", s == 200, b)
check("entra con la contraseña nueva", req("POST", "/collections/users/auth-with-password",
      {"identity": correo, "password": "clave-nueva-123"})[0] == 200)
check("secretaría no puede cambiar la contraseña de otro",
      req("PATCH", f"/collections/users/records/{u['id']}", {"password": "otra-clave-123", "passwordConfirm": "otra-clave-123"}, sec)[0] in (400, 403, 404))
req("DELETE", f"/collections/users/records/{u['id']}", token=admin)

# --- Observaciones -----------------------------------------------------------------
s, o = req("POST", "/collections/observaciones/records", {
    "tipo": "problema", "texto": "  La foto sale movida  ", "pantalla": "captura",
    "usuario": admin_id, "estado": "resuelta", "respuesta": "inventada"}, sec)
check("anotar una observación", s == 200, o)
check("el autor, el estado y la respuesta los pone el servidor",
      o.get("usuario") == sec_id and o.get("estado") == "pendiente" and o.get("respuesta") == ""
      and o.get("texto") == "La foto sale movida", o)
check("una observación vacía no se guarda",
      req("POST", "/collections/observaciones/records", {"tipo": "duda", "texto": "   "}, sec)[0] == 400)
req("POST", "/collections/observaciones/records", {"tipo": "sugerencia", "texto": "Nota del administrador"}, admin)
s, lista = req("GET", "/collections/observaciones/records?perPage=200", token=sec)
check("cada usuario ve solo las suyas", s == 200 and lista["items"] and all(x["usuario"] == sec_id for x in lista["items"]), lista)
s, lista = req("GET", "/collections/observaciones/records?perPage=200", token=admin)
check("el administrador ve todas", s == 200 and {sec_id, admin_id} <= {x["usuario"] for x in lista["items"]})
check("secretaría no puede responder",
      req("PATCH", f"/collections/observaciones/records/{o['id']}", {"estado": "resuelta"}, sec)[0] in (403, 404))
s, o2 = req("PATCH", f"/collections/observaciones/records/{o['id']}",
            {"estado": "resuelta", "respuesta": "Usar el trípode", "texto": "cambiado"}, admin)
check("el administrador responde sin cambiar el texto",
      s == 200 and o2["estado"] == "resuelta" and o2["respuesta"] == "Usar el trípode" and o2["texto"] == "La foto sale movida", o2)

# --- Datos para el informe y la exportación ----------------------------------------
sufijo = uuid.uuid4().hex[:6]
libro = req("POST", "/collections/libros/records", {"numero": f"Piloto {sufijo}", "sacramento": "bautismo"}, sec)[1]["id"]
p10 = subir_pagina(libro, "10", sec)
p2 = subir_pagina(libro, "2", sec)
ACTAS = [
    (p10, 1, {"nombres": "Ana", "apellidos": "Ruiz", "estado": "revisada", "fecha_bautizo": "1980-01-05"}),
    (p2, 1, {"nombres": "=HYPERLINK(\"http://x\")", "apellidos": "Prueba", "estado": "borrador"}),
    (p2, 2, {"nombres": "Luis; José", "apellidos": "\"El Chino\" Paz", "estado": "borrador",
             "notas_marginales": "Línea 1\nLínea 2"}),
]
for pagina, pos, datos in ACTAS:
    s, b = req("POST", "/collections/actas_bautismo/records", {"pagina": pagina, "posicion": pos, **datos}, sec)
    if s != 200:
        print("no se pudo crear el acta", b)

# --- Informe -------------------------------------------------------------------------
check("secretaría no ve el informe", req("GET", "/piloto/informe", token=sec)[0] == 403)
s, inf = req("GET", "/piloto/informe", token=admin)
check("informe del piloto", s == 200, inf)
fila = next((l for l in inf.get("libros", []) if l["id"] == libro), {})
check("avance del libro", fila.get("paginas") == 2 and fila.get("actas") == 3 and fila.get("borradores") == 2, fila)
yo = next((p for p in inf.get("personas", []) if p["nombre"] == sec_nombre), {})
check("ritmo por persona", yo.get("actas", 0) >= 3 and yo.get("minutos", 0) >= 3, yo)
hoy = datetime.date.today().isoformat()
check("actividad de hoy", any(d["dia"] == hoy and d["actas"] >= 3 and d["paginas"] >= 2 for d in inf.get("por_dia", [])),
      inf.get("por_dia", [])[:3])
check("observaciones pendientes y espacio usado",
      inf.get("observaciones_pendientes", 0) >= 1 and inf["espacio"]["base"] > 0 and inf["espacio"]["imagenes"] > 0, inf.get("espacio"))

# --- Exportar ------------------------------------------------------------------------
check("secretaría no puede exportar", req("GET", f"/exportar/actas/{libro}", token=sec)[0] == 403)
check("libro inexistente", req("GET", "/exportar/actas/noexiste123456", token=admin)[0] == 404)
s, (cuerpo, cabeceras) = req("GET", f"/exportar/actas/{libro}", token=admin, crudo=True)
texto = cuerpo.decode("utf-8")
check("exportar actas a CSV para Excel", s == 200 and "text/csv" in cabeceras.get("Content-Type", "")
      and texto.startswith("﻿Libro;Folio;"), (s, cabeceras.get("Content-Type"), texto[:60]))
filas = list(csv.reader(io.StringIO(texto.lstrip("﻿")), delimiter=";"))
check("una fila por acta, ordenadas por folio", len(filas) == 4 and [f[1] for f in filas[1:]] == ["2", "2", "10"],
      [f[:5] for f in filas])
nombres = [f[4] for f in filas[1:]]
check("las fórmulas no se ejecutan en Excel", nombres[0].startswith("'="), nombres)
check("punto y coma, comillas y saltos de línea se conservan",
      nombres[1] == "Luis; José" and filas[2][5] == "\"El Chino\" Paz" and filas[2][16] == "Línea 1\nLínea 2", filas[2])
check("incluye quién la creó", filas[1][20] == sec_nombre, filas[1][18:22])
check("fecha de creación en hora local", filas[1][22].startswith(hoy + " ") and len(filas[1][22]) == 16, filas[1][22:])

print(f"\n{'Todo bien' if not fallas else f'{fallas} fallas'}")
