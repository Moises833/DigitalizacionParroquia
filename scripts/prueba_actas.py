"""Pruebas de segmentación de páginas y registro de actas.

Uso: con el servidor recién creado, ejecutar en orden prueba_api.py,
prueba_captura.py y este archivo (usa las páginas que sube prueba_captura.py).
"""
import json
import urllib.error
import urllib.parse
import urllib.request

B = "http://127.0.0.1:8090/api"


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


def check(nombre, cond, extra=""):
    print(("OK   " if cond else "FALLA") + " " + nombre + (" -> " + str(extra) if not cond else ""))


def login(email):
    b = req("POST", "/collections/users/auth-with-password", {"identity": email, "password": "clave-prueba-123"})[1]
    return b["token"], b["record"]["id"]


def q(texto):
    return urllib.parse.quote(texto)


admin, admin_id = login("admin@prueba.test")
sec, sec_id = login("sec@prueba.test")
tomo1 = req("GET", "/collections/libros/records?filter=" + q("numero='Tomo I'"), token=admin)[1]["items"][0]["id"]
tomo2 = req("POST", "/collections/libros/records", {"numero": "Tomo II", "sacramento": "bautismo"}, admin)[1]["id"]
pagina = req("GET", "/collections/paginas/records?sort=created&filter=" + q(f"libro='{tomo1}'"), token=sec)[1]["items"][0]
pid = pagina["id"]

franjas = [{"x": 0.02, "y": i / 3, "ancho": 0.96, "alto": 1 / 3 - 0.001} for i in range(3)]
s, b = req("PATCH", f"/collections/paginas/records/{pid}", {"segmentos": franjas}, sec)
check("guardar 3 recuadros -> segmentada", s == 200 and b["estado"] == "segmentada", b)

s, b = req("PATCH", f"/collections/paginas/records/{pid}", {"segmentos": [{"x": 0.5, "y": 0, "ancho": 0.7, "alto": 0.2}]}, sec)
check("rechaza recuadro fuera de la imagen", s == 400, b)
s, b = req("PATCH", f"/collections/paginas/records/{pid}", {"segmentos": franjas * 3}, sec)
check("rechaza más de 6 recuadros", s == 400, b)

def acta(pos, **extra):
    datos = {"pagina": pid, "libro": tomo2, "posicion": pos, "numero_acta": str(140 + pos),
             "nombres": f"Persona {pos}", "apellidos": "Pérez", "estado": "borrador",
             "ministro": "P. Victorino Miguélez", "recorte": franjas[pos - 1], "creado_por": admin_id}
    datos.update(extra)
    return req("POST", "/collections/actas_bautismo/records", datos, sec)

s, b = acta(1)
check("crea acta 1", s == 200, b)
a1 = b.get("id")
check("el libro se toma de la página (no del cliente)", b.get("libro") == tomo1, b.get("libro"))
check("creado_por es quien guarda (no el que envía el cliente)", b.get("creado_por") == sec_id, b.get("creado_por"))
check("borrador no tiene revisor", b.get("revisado_por") == "", b)

s, b = acta(1)
check("rechaza dos actas en la misma posición", s == 400, b)

s, b = req("GET", f"/collections/paginas/records/{pid}", token=sec)
check("con 1 de 3 actas sigue segmentada", b["estado"] == "segmentada", b["estado"])

s, b = req("PATCH", f"/collections/actas_bautismo/records/{a1}", {"estado": "revisada"}, sec)
check("al marcar revisada se registra el revisor", b.get("revisado_por") == sec_id, b)
s, b = req("PATCH", f"/collections/actas_bautismo/records/{a1}", {"observaciones": "x"}, admin)
check("editar otro campo no cambia el revisor", b.get("revisado_por") == sec_id, b)
s, b = req("PATCH", f"/collections/actas_bautismo/records/{a1}", {"creado_por": admin_id}, admin)
check("no se puede cambiar quién la creó", b.get("creado_por") == sec_id, b)

acta(2, ministro="P. Paolo Borelli")
s, b = acta(3, ministro="P. Victorino Miguélez")
a3 = b.get("id")
s, b = req("GET", f"/collections/paginas/records/{pid}", token=sec)
check("con 3 de 3 actas queda completa", b["estado"] == "completa", b["estado"])

s, b = req("DELETE", f"/collections/actas_bautismo/records/{a3}", token=admin)
s, b = req("GET", f"/collections/paginas/records/{pid}", token=sec)
check("al borrar una acta vuelve a segmentada", s == 200 and b["estado"] == "segmentada", b)

s, b = req("PATCH", f"/collections/paginas/records/{pid}", {"segmentos": franjas[:2]}, sec)
check("si quedan 2 recuadros y 2 actas, completa", b.get("estado") == "completa", b)

s, b = req("GET", "/sugerencias/ministro", token=sec)
check("sugerencias ordenadas por frecuencia", b == ["P. Victorino Miguélez", "P. Paolo Borelli"], b)
s, b = req("GET", "/sugerencias/nombres", token=sec)
check("sugerencias solo para campos permitidos", s == 400, b)
s, b = req("GET", "/sugerencias/ministro")
check("sugerencias exigen sesión", s == 401, b)
