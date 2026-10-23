"""Pruebas de la vinculación del teléfono (sesiones de captura).

Uso: con el servidor recién creado, ejecutar primero `python scripts/prueba_api.py`
(crea el administrador, la secretaria y el «Tomo I») y luego este archivo.
"""
import json
import struct
import urllib.error
import urllib.parse
import urllib.request
import uuid
import zlib

B = "http://127.0.0.1:8090/api"


def req(method, path, body=None, token=None, multipart=None):
    headers = {}
    data = None
    if multipart is not None:
        limite = uuid.uuid4().hex
        partes = []
        for nombre, (archivo, contenido, tipo) in multipart.items():
            partes.append(
                f'--{limite}\r\nContent-Disposition: form-data; name="{nombre}"; filename="{archivo}"\r\n'
                f"Content-Type: {tipo}\r\n\r\n".encode() + contenido + b"\r\n"
            )
        data = b"".join(partes) + f"--{limite}--\r\n".encode()
        headers["Content-Type"] = f"multipart/form-data; boundary={limite}"
    elif body is not None:
        data = json.dumps(body).encode("utf-8")
        headers["Content-Type"] = "application/json"
    if token:
        headers["Authorization"] = token
    r = urllib.request.Request(B + path, data=data, method=method, headers=headers)
    try:
        with urllib.request.urlopen(r) as resp:
            return resp.status, json.loads(resp.read() or b"{}")
    except urllib.error.HTTPError as e:
        return e.code, json.loads(e.read() or b"{}")


def png(ancho=40, alto=30):
    fila = b"\x00" + b"\xc8\xb4\x96" * ancho
    crudo = zlib.compress(fila * alto)

    def chunk(tipo, datos):
        return struct.pack(">I", len(datos)) + tipo + datos + struct.pack(">I", zlib.crc32(tipo + datos))

    return (b"\x89PNG\r\n\x1a\n" + chunk(b"IHDR", struct.pack(">IIBBBBB", ancho, alto, 8, 2, 0, 0, 0))
            + chunk(b"IDAT", crudo) + chunk(b"IEND", b""))


def check(nombre, cond, extra=""):
    print(("OK   " if cond else "FALLA") + " " + nombre + (" -> " + str(extra) if not cond else ""))


def login(email):
    return req("POST", "/collections/users/auth-with-password", {"identity": email, "password": "clave-prueba-123"})[1]["token"]


admin = login("admin@prueba.test")
sec = login("sec@prueba.test")
libro = req("GET", "/collections/libros/records?filter=" + urllib.parse.quote("numero='Tomo I'"), token=admin)[1]["items"][0]["id"]
imagen = {"imagen": ("pagina.png", png(), "image/png")}

s, b = req("POST", "/captura/sesiones", {"libro": libro})
check("abrir vinculación exige sesión", s == 401, b)

s, b = req("POST", "/captura/sesiones", {"libro": libro}, sec)
check("secretaria abre vinculación", s == 201 and len(b.get("token", "")) == 40, b)
token, sesion_id = b.get("token"), b.get("id")
check("folio inicial es 1 en libro vacío", b.get("folio_siguiente") == 1, b)
check("devuelve direcciones de red", isinstance(b.get("direcciones"), list), b)

s, b = req("GET", f"/captura/movil/{token}")
check("teléfono ve el libro sin cuenta", s == 200 and b["libro"]["numero"] == "Tomo I", b)
s, b = req("GET", "/captura/movil/token-inventado")
check("token falso es rechazado", s == 404, b)

s, b = req("POST", f"/captura/movil/{token}/paginas", multipart=imagen)
check("teléfono sube foto -> folio 1", s == 201 and b.get("folio") == "1", b)
s, b = req("POST", f"/captura/movil/{token}/paginas", multipart=imagen)
check("segunda foto -> folio 2", s == 201 and b.get("folio") == "2", b)

s, b = req("POST", f"/captura/movil/{token}/paginas", multipart={"imagen": ("x.txt", b"hola", "text/plain")})
check("rechaza archivo que no es imagen", s == 400, b)

s, b = req("PATCH", f"/captura/sesiones/{sesion_id}", {"folio_siguiente": 10}, sec)
check("PC cambia el próximo folio", s == 200 and b["folio_siguiente"] == 10, b)
s, b = req("POST", f"/captura/movil/{token}/paginas", multipart=imagen)
check("siguiente foto usa el folio 10", b.get("folio") == "10", b)
s, b = req("PATCH", f"/captura/sesiones/{sesion_id}", {"folio_siguiente": 2}, sec)
s, b = req("POST", f"/captura/movil/{token}/paginas", multipart=imagen)
check("salta folios ya ocupados (2 -> 3)", b.get("folio") == "3", b)

s, b = req("GET", "/collections/paginas/records?sort=created&filter=" + urllib.parse.quote(f"libro='{libro}'"), token=sec)
paginas = b["items"]
check("4 páginas guardadas con origen móvil", len(paginas) == 4 and all(p["origen"] == "movil" for p in paginas), paginas)

s, b = req("GET", f"/collections/sesiones_captura/records/{sesion_id}", token=sec)
check("contador de fotos = 4", b.get("fotos_recibidas") == 4, b)
check("el hash del token no se expone", "token_hash" not in b, b)

s, b = req("PATCH", f"/collections/sesiones_captura/records/{sesion_id}", {"folio_siguiente": 99}, sec)
check("API genérica no permite editar la sesión", s in (403, 404), b)

s, b = req("PATCH", f"/captura/sesiones/{sesion_id}", {"activa": False}, admin)
check("admin puede cerrar la vinculación", s == 200, b)
s, b = req("POST", f"/captura/movil/{token}/paginas", multipart=imagen)
check("vinculación cerrada rechaza fotos", s == 404, b)

# Abrir una nueva cierra la anterior del mismo usuario.
s1, b1 = req("POST", "/captura/sesiones", {"libro": libro}, sec)
check("nueva vinculación continúa en folio 11", b1.get("folio_siguiente") == 11, b1)
s2, b2 = req("POST", "/captura/sesiones", {"libro": libro}, sec)
s, b = req("GET", f"/captura/movil/{b1['token']}")
check("abrir otra cierra la anterior", s == 404, b)
s, b = req("GET", f"/captura/movil/{b2['token']}")
check("la más reciente sigue activa", s == 200, b)

p = paginas[0]
url_imagen = f"/files/paginas/{p['id']}/{p['imagen']}"
s, b = req("GET", url_imagen)
check("imagen no se descarga sin token", s in (403, 404), s)
ft = req("POST", "/files/token", token=sec)[1].get("token")
try:
    with urllib.request.urlopen(B + url_imagen + "?token=" + ft) as r:
        ok = r.status == 200 and r.headers["Content-Type"] == "image/png"
except urllib.error.HTTPError as e:
    ok = e.code
check("imagen se descarga con token de archivo", ok is True, ok)

s, b = req("GET", "/collections/auditoria/records?perPage=100&filter=" + urllib.parse.quote("coleccion='paginas'"), token=admin)
check("auditoría registra las fotos del teléfono", b.get("totalItems") == 4, b.get("totalItems"))
