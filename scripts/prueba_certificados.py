"""Pruebas de la fe de bautismo (Fase 6).

Uso: después de prueba_api.py (usa sus usuarios). Crea su propio libro y actas.
"""
import datetime
import json
import struct
import urllib.error
import urllib.request
import uuid
import zlib

B = "http://127.0.0.1:8090/api"
fallas = 0


def req(method, path, body=None, token=None):
    data = json.dumps(body).encode("utf-8") if body is not None else None
    r = urllib.request.Request(B + path, data=data, method=method)
    r.add_header("Content-Type", "application/json")
    if token:
        r.add_header("Authorization", token)
    try:
        with urllib.request.urlopen(r) as resp:
            return resp.status, json.loads(resp.read() or b"{}")
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
    print(("OK   " if cond else "FALLA") + " " + nombre + (" -> " + str(extra) if not cond else ""))


def login(email):
    return req("POST", "/collections/users/auth-with-password", {"identity": email, "password": "clave-prueba-123"})[1]


sesion = login("sec@prueba.test")
sec, sec_id = sesion["token"], sesion["record"]["id"]
admin = login("admin@prueba.test")["token"]
sufijo = uuid.uuid4().hex[:6]
libro = req("POST", "/collections/libros/records", {"numero": f"Libro Cert {sufijo}", "sacramento": "bautismo"}, sec)[1]["id"]
pagina = subir_pagina(libro, "7", sec)

s, acta = req("POST", "/collections/actas_bautismo/records", {
    "pagina": pagina, "posicion": 1, "estado": "borrador", "nombres": "Rosa Elena", "apellidos": "Pérez Díaz",
    "sexo": "F", "fecha_bautizo": "1985-03-10", "fecha_nacimiento": "1985-01-02", "padre": "Juan Pérez",
    "madre": "Elena Díaz", "padrino": "Luis Mora", "madrina": "Ana Gil", "ministro": "Pbro. Tomás Ruiz",
    "numero_acta": "120", "notas_marginales": "Contrajo matrimonio el 5 de junio de 2010.",
}, sec)
acta_id = acta["id"]

s, vista = req("GET", f"/certificados/vista/{acta_id}", token=sec)
check("vista previa con libro, folio y parroquia",
      s == 200 and vista["datos"]["libro"] == f"Libro Cert {sufijo}" and vista["datos"]["folio"] == "7"
      and vista["datos"]["parroquia"]["nombre"] and vista["estado"] == "borrador", vista)
check("vista previa necesita sesión", req("GET", f"/certificados/vista/{acta_id}")[0] in (401, 403))
check("vista previa de un acta inexistente", req("GET", "/certificados/vista/noexiste123456", token=sec)[0] == 404)

s, b = req("POST", "/collections/certificados/records", {"acta": acta_id}, sec)
check("no se certifica un acta en borrador", s == 400 and "revis" in json.dumps(b, ensure_ascii=False), (s, b))

req("PATCH", f"/collections/actas_bautismo/records/{acta_id}", {"estado": "revisada"}, sec)

anio = datetime.date.today().year
s, c1 = req("POST", "/collections/certificados/records", {
    "acta": acta_id, "fines": " Matrimonio ", "firmante": "Pbro. Carlos Gómez", "cargo": "Párroco",
    "fecha_expedicion": "2026-10-22",
    # Lo que sigue lo decide el servidor y debe ignorarse
    "numero": "9999-9999", "correlativo": 9999, "emitido_por": "", "datos": {"nombres": "Falso"},
}, sec)
check("emitir certificado", s == 200, (s, c1))
check("número correlativo del año", c1.get("numero", "").startswith(f"{anio}-") and c1.get("anio") == anio
      and c1["numero"] == f"{anio}-{c1['correlativo']:04d}", c1.get("numero"))
check("el servidor guarda una copia de los datos", c1.get("datos", {}).get("nombres") == "Rosa Elena"
      and c1["datos"]["notas_marginales"].startswith("Contrajo"), c1.get("datos"))
check("queda registrado quién lo emitió", c1.get("emitido_por") == sec_id, c1.get("emitido_por"))
check("se limpian espacios", c1.get("fines") == "Matrimonio", c1.get("fines"))

s, c2 = req("POST", "/collections/certificados/records", {"acta": acta_id}, sec)
check("el siguiente toma el número siguiente", s == 200 and c2["correlativo"] == c1["correlativo"] + 1, (s, c2))
check("valores por defecto: hoy y Párroco",
      c2.get("fecha_expedicion") == datetime.date.today().isoformat() and c2.get("cargo") == "Párroco", c2)

req("PATCH", f"/collections/actas_bautismo/records/{acta_id}", {"nombres": "Rosa Elena María"}, sec)
s, guardado = req("GET", f"/collections/certificados/records/{c1['id']}", token=sec)
check("corregir el acta no cambia lo ya emitido", guardado["datos"]["nombres"] == "Rosa Elena", guardado.get("datos"))

check("no se puede editar un certificado",
      req("PATCH", f"/collections/certificados/records/{c1['id']}", {"fines": "otro"}, admin)[0] in (403, 404))
check("no se puede borrar un certificado",
      req("DELETE", f"/collections/certificados/records/{c1['id']}", token=admin)[0] in (403, 404))
check("un acta con certificados no se puede borrar",
      req("DELETE", f"/collections/actas_bautismo/records/{acta_id}", token=admin)[0] == 400)
check("sin sesión no se emite", req("POST", "/collections/certificados/records", {"acta": acta_id})[0] in (400, 401, 403))

s, lista = req("GET", f"/collections/certificados/records?filter=acta%3D'{acta_id}'&sort=-correlativo&expand=emitido_por", token=sec)
check("lista de certificados del acta", s == 200 and lista["totalItems"] == 2
      and lista["items"][0]["expand"]["emitido_por"]["id"] == sec_id, lista)

print(f"\n{'Todo bien' if not fallas else f'{fallas} fallas'}")
