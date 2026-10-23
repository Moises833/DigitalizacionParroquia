"""Pruebas de la API contra un servidor recién creado (pb_data vacío).

Uso: iniciar `pocketbase.exe serve` con datos limpios y ejecutar `python scripts/prueba_api.py`.
"""
import json
import urllib.request
import urllib.error

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
    print(("OK   " if cond else "FALLA") + " " + nombre + (" -> " + str(extra) if extra and not cond else ""))


s, b = req("GET", "/configuracion-inicial")
check("configuracion pendiente", s == 200 and b["pendiente"] is True, b)

s, b = req("POST", "/configuracion-inicial", {"nombre": "Admin", "email": "admin@prueba.test", "password": "corta", "parroquia": "X"})
check("rechaza contraseña corta", s == 400, b)

s, b = req("POST", "/configuracion-inicial", {"nombre": "Admin Prueba", "email": "admin@prueba.test", "password": "clave-prueba-123", "parroquia": "Parroquia San José"})
check("crea primer admin", s == 201, b)

s, b = req("POST", "/configuracion-inicial", {"nombre": "Otro", "email": "otro@prueba.test", "password": "clave-prueba-123", "parroquia": "Y"})
check("bloquea segundo setup", s == 403, b)

s, b = req("POST", "/collections/users/auth-with-password", {"identity": "admin@prueba.test", "password": "clave-prueba-123"})
check("login admin", s == 200 and b["record"]["rol"] == "admin", b)
tok = b.get("token")

s, b = req("GET", "/collections/parroquia/records", token=tok)
check("nombre de parroquia guardado", b["items"][0]["nombre"] == "Parroquia San José", b)

s, b = req("GET", "/collections/actas_bautismo/records")
check("anónimo no ve actas", b.get("totalItems") == 0, b)

s, b = req("POST", "/collections/libros/records", {"numero": "Tomo I", "sacramento": "bautismo", "anio_inicio": 1950}, tok)
check("crea libro", s == 200, b)
libro = b.get("id")

s, b = req("POST", "/collections/actas_bautismo/records", {
    "libro": libro, "nombres": "Olimar", "apellidos": "Zambrano Núñez", "estado": "borrador",
    "fecha_bautizo": "1981-05-21", "ministro": "P. Victorino Miguélez"}, tok)
check("crea acta", s == 200, b)
acta = b.get("id")
check("busqueda sin acentos", "zambrano nunez" in b.get("busqueda", ""), b.get("busqueda"))

s, b = req("PATCH", f"/collections/actas_bautismo/records/{acta}", {"padre": "Manuel María Zambrano"}, tok)
check("edita acta y actualiza busqueda", s == 200 and "manuel maria zambrano" in b["busqueda"], b)

s, b = req("PATCH", f"/collections/actas_bautismo/records/{acta}", {"fecha_bautizo": "21 de mayo"}, tok)
check("rechaza fecha mal formada", s == 400, b)

s, b = req("GET", "/collections/actas_bautismo/records?filter=" + urllib.parse.quote("busqueda~'NUNEZ'"), token=tok)
check("busca 'NUNEZ' encuentra Núñez", b.get("totalItems") == 1, b)

# Usuario secretaria no puede ascenderse
s, b = req("POST", "/collections/users/records", {"email": "sec@prueba.test", "password": "clave-prueba-123", "passwordConfirm": "clave-prueba-123", "rol": "secretaria", "name": "Sec"}, tok)
check("admin crea secretaria", s == 200, b)
sec_id = b.get("id")
s, b = req("POST", "/collections/users/auth-with-password", {"identity": "sec@prueba.test", "password": "clave-prueba-123"})
tok_sec = b.get("token")
s, b = req("PATCH", f"/collections/users/records/{sec_id}", {"rol": "admin"}, tok_sec)
check("secretaria no puede cambiarse el rol", s in (400, 403, 404), b)
s, b = req("PATCH", f"/collections/users/records/{sec_id}", {"name": "Secretaria Parroquial"}, tok_sec)
check("secretaria sí edita su nombre", s == 200, b)
s, b = req("DELETE", f"/collections/actas_bautismo/records/{acta}", token=tok_sec)
check("secretaria no puede borrar actas", s in (400, 403, 404), b)
s, b = req("GET", "/collections/auditoria/records", token=tok_sec)
check("secretaria no ve auditoría", b.get("totalItems") == 0, b)

s, b = req("GET", "/collections/auditoria/records?sort=created&perPage=50", token=tok)
acciones = [(r["accion"], r["coleccion"]) for r in b["items"]]
check("auditoría registra creación de acta", ("crear", "actas_bautismo") in acciones, acciones)
ediciones = [r for r in b["items"] if r["accion"] == "editar" and r["coleccion"] == "actas_bautismo"]
check("auditoría guarda solo el cambio", len(ediciones) == 1 and ediciones[0]["despues"].get("padre") == "Manuel María Zambrano", ediciones)
check("auditoría registra al usuario", all(r["usuario"] for r in b["items"]), b["items"])
