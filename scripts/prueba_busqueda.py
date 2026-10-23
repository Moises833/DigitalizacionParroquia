"""Pruebas de la búsqueda de actas (Fase 5).

Uso: después de prueba_api.py (usa sus usuarios). Crea su propio libro y actas.
Las consultas son las mismas que arma web/src/lib/busqueda.js.
"""
import json
import struct
import urllib.error
import urllib.parse
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
    return req("POST", "/collections/users/auth-with-password", {"identity": email, "password": "clave-prueba-123"})[1]["token"]


sec = login("sec@prueba.test")
libro = req("POST", "/collections/libros/records", {"numero": "Libro Búsqueda", "sacramento": "bautismo"}, sec)[1]["id"]
otro = req("POST", "/collections/libros/records", {"numero": "Libro Otro", "sacramento": "bautismo"}, sec)[1]["id"]
p12 = subir_pagina(libro, "12", sec)
p13 = subir_pagina(libro, "13", sec)
p_otro = subir_pagina(otro, "12", sec)

ACTAS = [
    (p12, 1, {"nombres": "María José", "apellidos": "Núñez Ávila", "padre": "José Núñez", "madre": "Ana Ávila",
              "padrino": "Pedro Castillo", "madrina": "Rosa Méndez", "fecha_bautizo": "1981-05-21", "numero_acta": "34"}),
    (p12, 2, {"nombres": "Carlos", "apellidos": "Gómez", "padre": "Luis Gómez", "madre": "María Núñez",
              "padrino": "José Lara", "madrina": "", "fecha_bautizo": "1983-02-01", "numero_acta": "35"}),
    (p13, 1, {"nombres": "Ana Lucía", "apellidos": "Torres", "padre": "Mario Torres", "madre": "Julia Vega",
              "padrino": "Hugo Lara", "madrina": "María José Núñez", "fecha_bautizo": "1990-12-31", "numero_acta": "36"}),
    (p_otro, 1, {"nombres": "José", "apellidos": "Núñez", "fecha_bautizo": "1981-07-07", "numero_acta": "34"}),
]
ids = {}
for pagina, pos, datos in ACTAS:
    s, b = req("POST", "/collections/actas_bautismo/records", {"pagina": pagina, "posicion": pos, "estado": "borrador", **datos}, sec)
    ids[datos["nombres"]] = b.get("id")
    if s != 200:
        print("no se pudo crear", datos["nombres"], b)

a = req("GET", f"/collections/actas_bautismo/records/{ids['María José']}", token=sec)[1]
check("campos normalizados por persona",
      a["b_nombre"] == "maria jose | nunez avila" and a["b_padres"] == "jose nunez | ana avila"
      and a["b_padrinos"] == "pedro castillo | rosa mendez", a)

s, a = req("PATCH", f"/collections/actas_bautismo/records/{ids['Carlos']}", {"madrina": "Élida Ruiz"}, sec)
check("se actualizan al editar", a.get("b_padrinos") == "jose lara | elida ruiz", a.get("b_padrinos"))


def buscar(filtro, sort="apellidos,nombres"):
    s, b = req("GET", "/collections/actas_bautismo/records?perPage=50&sort=" + sort + "&filter="
               + urllib.parse.quote(filtro) + "&expand=libro,pagina", token=sec)
    if s != 200:
        print("   error de filtro:", b)
        return []
    return [x["nombres"] for x in b["items"] if x["libro"] in (libro, otro)]


check("nombre sin acentos y en cualquier orden",
      sorted(buscar("b_nombre ~ 'nunez' && b_nombre ~ 'maria'")) == ["María José"], buscar("b_nombre ~ 'nunez' && b_nombre ~ 'maria'"))
check("nombre con acentos y mayúsculas no importan (la interfaz normaliza)",
      sorted(buscar("b_nombre ~ 'jose'")) == ["José", "María José"], buscar("b_nombre ~ 'jose'"))
check("buscar por padres", buscar("b_padres ~ 'nunez'") == ["Carlos", "María José"] or
      sorted(buscar("b_padres ~ 'nunez'")) == ["Carlos", "María José"], buscar("b_padres ~ 'nunez'"))
check("buscar por padrinos", sorted(buscar("b_padrinos ~ 'lara'")) == ["Ana Lucía", "Carlos"], buscar("b_padrinos ~ 'lara'"))
check("el nombre de una madrina no aparece como bautizado",
      "Ana Lucía" not in buscar("b_nombre ~ 'maria' && b_nombre ~ 'nunez'"))
check("por libro", sorted(buscar(f"b_nombre ~ 'jose' && libro = '{otro}'")) == ["José"])
check("por año exacto", sorted(buscar("fecha_bautizo >= '1981-01-01' && fecha_bautizo <= '1981-12-31'")) == ["José", "María José"],
      buscar("fecha_bautizo >= '1981-01-01' && fecha_bautizo <= '1981-12-31'"))
check("por rango de años incluye el 31 de diciembre",
      sorted(buscar("fecha_bautizo >= '1983-01-01' && fecha_bautizo <= '1990-12-31'")) == ["Ana Lucía", "Carlos"])
check("por folio (de la página)", sorted(buscar(f"pagina.folio = '12' && libro = '{libro}'")) == ["Carlos", "María José"])
check("por folio en todos los libros", sorted(buscar("pagina.folio = '12' && b_nombre ~ 'jose'")) == ["José", "María José"])
check("por número de acta", sorted(buscar("numero_acta = '34'")) == ["José", "María José"])

s, b = req("GET", "/collections/actas_bautismo/records?perPage=5&filter=" + urllib.parse.quote("b_nombre ~ 'jose'")
           + "&expand=libro,pagina&fields=" + urllib.parse.quote("id,nombres,expand.libro.numero,expand.pagina.folio"), token=sec)
item = next((x for x in b.get("items", []) if x["nombres"] == "María José"), {})
check("resultados traen libro y folio", item.get("expand", {}).get("libro", {}).get("numero") == "Libro Búsqueda"
      and item["expand"]["pagina"]["folio"] == "12", item)

s, b = req("GET", "/collections/actas_bautismo/records?filter=" + urllib.parse.quote("b_nombre ~ 'jose'"))
check("sin sesión no se puede buscar", s != 200 or b.get("totalItems") == 0, (s, b.get("totalItems")))

print(f"\n{'Todo bien' if not fallas else f'{fallas} fallas'}")
