"""Pruebas de la cola de transcripción con IA, contra un Gemini simulado.

Uso: iniciar el servidor apuntando al simulador y sin la tarea programada,
  IA_URL_BASE=http://127.0.0.1:8099 IA_SIN_CRON=1 ./pocketbase.exe serve
y ejecutar este archivo después de prueba_api.py (usa sus usuarios).
Ningún dato sale de la computadora.
"""
import base64
import json
import struct
import threading
import urllib.error
import urllib.parse
import urllib.request
import uuid
import zlib
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

B = "http://127.0.0.1:8090/api"
CLAVE = "clave-falsa-123"

# --- Gemini simulado -------------------------------------------------------

simulador = {"modo": "ok", "pedidos": []}

RESPUESTA = {
    "numero_acta": "34", "nombres": "María Fernanda", "apellidos": "López Ruiz", "sexo": "f",
    "fecha_nacimiento": "1981-13-40", "fecha_nacimiento_texto": "tres de marzo de mil novecientos ochenta y uno",
    "lugar_nacimiento": "Managua", "fecha_bautizo": "1981-05-21",
    "fecha_bautizo_texto": "veintiuno de mayo de mil novecientos ochenta y uno",
    "padre": "José López", "madre": "Ana Ruiz", "padrino": "Pedro Castillo", "madrina": "Rosa Méndez",
    "ministro": "P. Juan Pérez", "registro_civil": "", "notas_marginales": "", "observaciones": "",
    "texto_completo": "En la parroquia…", "campos_dudosos": ["padrino", "inventado"], "campo_extra": "x",
}


class Gemini(BaseHTTPRequestHandler):
    def log_message(self, *args):
        pass

    def responder(self, codigo, cuerpo):
        datos = json.dumps(cuerpo).encode()
        self.send_response(codigo)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(datos)))
        self.end_headers()
        self.wfile.write(datos)

    def do_GET(self):
        if self.headers.get("x-goog-api-key") != CLAVE:
            return self.responder(400, {"error": {"message": "API key not valid."}})
        self.responder(200, {"models": [
            {"name": "models/gemini-prueba", "supportedGenerationMethods": ["generateContent"]},
            {"name": "models/gemini-otro", "supportedGenerationMethods": ["generateContent", "countTokens"]},
            {"name": "models/embedding-001", "supportedGenerationMethods": ["embedContent"]},
        ]})

    def do_POST(self):
        cuerpo = json.loads(self.rfile.read(int(self.headers["Content-Length"])))
        simulador["pedidos"].append({"ruta": self.path, "clave": self.headers.get("x-goog-api-key"), "cuerpo": cuerpo})
        if self.headers.get("x-goog-api-key") != CLAVE:
            return self.responder(400, {"error": {"message": "API key not valid. Please pass a valid API key."}})
        modo = simulador["modo"]
        if modo == "limite":
            return self.responder(429, {"error": {"message": "Resource exhausted"}})
        if modo == "falla":
            return self.responder(500, {"error": {"message": "Internal error"}})
        if modo == "basura":
            texto = "esto no es json"
        else:
            texto = json.dumps(RESPUESTA, ensure_ascii=False)
        self.responder(200, {"candidates": [{"content": {"parts": [{"text": texto}]}, "finishReason": "STOP"}]})


servidor = ThreadingHTTPServer(("127.0.0.1", 8099), Gemini)
threading.Thread(target=servidor.serve_forever, daemon=True).start()

# --- Cliente de la API -------------------------------------------------------


def req(method, path, body=None, token=None):
    data = json.dumps(body).encode("utf-8") if body is not None else None
    r = urllib.request.Request(B + path, data=data, method=method)
    r.add_header("Content-Type", "application/json")
    if token:
        r.add_header("Authorization", token)
    try:
        with urllib.request.urlopen(r, timeout=120) as resp:
            return resp.status, json.loads(resp.read() or b"{}")
    except urllib.error.HTTPError as e:
        return e.code, json.loads(e.read() or b"{}")


def multipart(method, path, campos, archivos, token):
    limite = uuid.uuid4().hex
    partes = []
    for k, v in campos.items():
        partes.append(f'--{limite}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode())
    for k, (nombre, tipo, datos) in archivos.items():
        partes.append(f'--{limite}\r\nContent-Disposition: form-data; name="{k}"; filename="{nombre}"\r\n'
                      f'Content-Type: {tipo}\r\n\r\n'.encode() + datos + b"\r\n")
    partes.append(f"--{limite}--\r\n".encode())
    r = urllib.request.Request(B + path, data=b"".join(partes), method=method)
    r.add_header("Content-Type", f"multipart/form-data; boundary={limite}")
    r.add_header("Authorization", token)
    try:
        with urllib.request.urlopen(r) as resp:
            return resp.status, json.loads(resp.read())
    except urllib.error.HTTPError as e:
        return e.code, json.loads(e.read() or b"{}")


def png(ancho, alto, color):
    fila = b"\x00" + bytes(color) * ancho
    def trozo(tipo, datos):
        return struct.pack(">I", len(datos)) + tipo + datos + struct.pack(">I", zlib.crc32(tipo + datos) & 0xFFFFFFFF)
    return (b"\x89PNG\r\n\x1a\n" + trozo(b"IHDR", struct.pack(">IIBBBBB", ancho, alto, 8, 2, 0, 0, 0))
            + trozo(b"IDAT", zlib.compress(fila * alto)) + trozo(b"IEND", b""))


fallas = 0


def check(nombre, cond, extra=""):
    global fallas
    fallas += 0 if cond else 1
    print(("OK   " if cond else "FALLA") + " " + nombre + (" -> " + str(extra) if not cond else ""))


def login(email):
    b = req("POST", "/collections/users/auth-with-password", {"identity": email, "password": "clave-prueba-123"})[1]
    return b["token"], b["record"]["id"]


admin, admin_id = login("admin@prueba.test")
sec, sec_id = login("sec@prueba.test")

# --- Configuración -------------------------------------------------------------

cfg = req("GET", "/collections/config_ia/records", token=sec)[1]["items"][0]
check("la configuración se lee sin la clave", "api_key" not in cfg and cfg["modelo"], cfg)
s, _ = req("PATCH", f"/collections/config_ia/records/{cfg['id']}", {"activo": True}, sec)
check("secretaría no puede cambiar la configuración", s in (403, 404), s)

s, _ = req("POST", "/ia/clave", {"api_key": CLAVE}, sec)
check("secretaría no puede guardar la clave", s == 403, s)
s, b = req("POST", "/ia/clave", {"api_key": CLAVE}, admin)
check("guardar clave: no se devuelve, queda su final", s == 200 and b == {"clave_fin": "-123"}, b)
s, b = req("PATCH", f"/collections/config_ia/records/{cfg['id']}",
           {"modelo": "gemini-prueba", "activo": True, "por_minuto": 60, "uso_dia": 999, "clave_fin": "zzzz"}, admin)
check("editar configuración: sin clave en la respuesta y la conserva", s == 200 and "api_key" not in b and b["clave_fin"] == "-123", b)
check("el uso diario no se puede falsear", b.get("uso_dia") == 0, b.get("uso_dia"))

s, b = req("POST", "/ia/probar", {}, admin)
check("probar conexión lista solo modelos Gemini de texto", s == 200 and b["modelos"] == ["gemini-prueba", "gemini-otro"], b)
s, _ = req("POST", "/ia/probar", {}, sec)
check("probar conexión: solo administrador", s == 403, s)

# --- Trabajos --------------------------------------------------------------------

libro = req("POST", "/collections/libros/records", {"numero": "Libro IA", "sacramento": "bautismo"}, admin)[1]["id"]
s, pagina = multipart("POST", "/collections/paginas/records",
                      {"libro": libro, "folio": "1", "estado": "pendiente", "origen": "pc"},
                      {"imagen": ("p.png", "image/png", png(60, 80, (230, 220, 200)))}, sec)
check("página de prueba", s == 200, pagina)
recorte = png(40, 20, (120, 90, 60))


def encolar(pos, **extra):
    campos = {"pagina": pagina["id"], "posicion": str(pos), "recorte": json.dumps({"x": 0, "y": 0, "ancho": 1, "alto": 0.3})}
    campos.update(extra)
    return multipart("POST", "/collections/trabajos_ia/records", campos, {"imagen": ("r.png", "image/png", recorte)}, sec)


s, t1 = encolar(1, estado="listo", resultado=json.dumps({"nombres": "falso"}), creado_por=admin_id)
check("encolar: el estado y el resultado los fija el servidor",
      s == 200 and t1["estado"] == "pendiente" and t1["resultado"] is None and t1["creado_por"] == sec_id, t1)
check("encolar: toma el libro de la página", t1.get("libro") == libro, t1.get("libro"))
s, _ = encolar(1)
check("no se repite la misma acta en la cola", s == 400, s)
s, _ = req("GET", f"/files/trabajos_ia/{t1['id']}/{t1['imagen']}")
check("el recorte enviado a la IA es un archivo protegido", s in (403, 404), s)

s, _ = req("POST", "/ia/procesar", {}, sec)
check("procesar ahora: solo administrador", s == 403, s)

simulador["modo"] = "ok"
s, b = req("POST", "/ia/procesar", {}, admin)
check("procesar: 1 transcrito", s == 200 and b["procesados"] == 1, b)
t = req("GET", f"/collections/trabajos_ia/records/{t1['id']}", token=sec)[1]
r = t["resultado"] or {}
check("resultado guardado y listo", t["estado"] == "listo" and r.get("nombres") == "María Fernanda" and t["modelo"] == "gemini-prueba", t)
check("sexo normalizado a M/F", r.get("sexo") == "F", r.get("sexo"))
check("fecha imposible descartada y marcada dudosa",
      r.get("fecha_nacimiento") == "" and "fecha_nacimiento" in r.get("campos_dudosos", []), r)
check("campos desconocidos descartados", "campo_extra" not in r and "inventado" not in r.get("campos_dudosos", []), r)
pedido = simulador["pedidos"][-1]
partes = pedido["cuerpo"]["contents"][0]["parts"]
imagen = next(p["inline_data"] for p in partes if "inline_data" in p)
check("a Gemini va el recorte exacto (base64) con la clave en cabecera",
      base64.b64decode(imagen["data"]) == recorte and imagen["mime_type"] == "image/png" and pedido["clave"] == CLAVE
      and pedido["ruta"].startswith("/v1beta/models/gemini-prueba:generateContent") and "key=" not in pedido["ruta"], pedido["ruta"])
check("se pide JSON con esquema", pedido["cuerpo"]["generationConfig"]["responseMimeType"] == "application/json")

estado = req("GET", "/ia/estado", token=sec)[1]
check("estado: 1 por revisar", estado["cola"]["por_revisar"] == 1 and estado["uso_dia"] == 1 and estado["activo"], estado)

# Al guardar el acta revisada deja de estar "por revisar".
s, _ = req("POST", "/collections/actas_bautismo/records",
           {"pagina": pagina["id"], "posicion": 1, "nombres": "María Fernanda", "estado": "revisada", "origen_datos": "ia"}, sec)
estado = req("GET", "/ia/estado", token=sec)[1]
check("con el acta guardada ya no queda por revisar", s == 200 and estado["cola"]["por_revisar"] == 0, estado)

# Límite de uso: vuelve a la cola sin gastar intentos.
s, t2 = encolar(2)
simulador["modo"] = "limite"
s, b = req("POST", "/ia/procesar", {}, admin)
t = req("GET", f"/collections/trabajos_ia/records/{t2['id']}", token=sec)[1]
check("límite 429: vuelve a pendiente sin contar intento", b["procesados"] == 0 and t["estado"] == "pendiente" and t["intentos"] == 0, (b, t))
estado = req("GET", "/ia/estado", token=sec)[1]
check("límite 429: se informa en el estado", "límite" in estado["ultimo_error"], estado["ultimo_error"])

# Fallos repetidos: tres intentos y queda en error.
simulador["modo"] = "falla"
for _ in range(3):
    req("POST", "/ia/procesar", {}, admin)
t = req("GET", f"/collections/trabajos_ia/records/{t2['id']}", token=sec)[1]
check("tras 3 fallos queda en error con el motivo", t["estado"] == "error" and t["intentos"] == 3 and "500" in t["error"], t)

simulador["modo"] = "ok"
s, b = req("POST", "/ia/reintentar", {}, admin)
t = req("GET", f"/collections/trabajos_ia/records/{t2['id']}", token=sec)[1]
check("reintentar errores", b.get("reintentados") == 1 and t["estado"] == "pendiente" and t["intentos"] == 0, (b, t))

simulador["modo"] = "basura"
req("POST", "/ia/procesar", {}, admin)
t = req("GET", f"/collections/trabajos_ia/records/{t2['id']}", token=sec)[1]
check("respuesta ilegible cuenta como intento fallido", t["intentos"] == 1 and "ilegible" in t["error"], t)

# Volver a enviar (p. ej. con un recorte nuevo) reinicia el trabajo.
s, t = multipart("PATCH", f"/collections/trabajos_ia/records/{t2['id']}", {"posicion": "5"},
                 {"imagen": ("r2.png", "image/png", png(30, 30, (0, 0, 0)))}, sec)
check("volver a enviar: reinicia y no cambia de acta", s == 200 and t["intentos"] == 0 and t["posicion"] == 2 and t["error"] == "", t)

# Clave inválida: no gasta intentos.
cfg_id = cfg["id"]
req("POST", "/ia/clave", {"api_key": "otra-clave"}, admin)
simulador["modo"] = "ok"
s, b = req("POST", "/ia/procesar", {}, admin)
t = req("GET", f"/collections/trabajos_ia/records/{t2['id']}", token=sec)[1]
check("clave inválida: se detiene y avisa", "clave" in b["motivo"] and t["estado"] == "pendiente" and t["intentos"] == 0, (b, t))

req("POST", "/ia/clave", {"api_key": CLAVE}, admin)
req("PATCH", f"/collections/config_ia/records/{cfg_id}", {"activo": False}, admin)
s, b = req("POST", "/ia/procesar", {}, admin)
check("desactivada: no procesa", b["procesados"] == 0 and "desactivada" in b["motivo"], b)

req("PATCH", f"/collections/config_ia/records/{cfg_id}", {"activo": True, "por_dia": 3}, admin)
s, b = req("POST", "/ia/procesar", {}, admin)
check("máximo diario respetado", b["procesados"] == 0 and "día" in b["motivo"], b)
req("PATCH", f"/collections/config_ia/records/{cfg_id}", {"por_dia": 200}, admin)
s, b = req("POST", "/ia/procesar", {}, admin)
check("con cupo vuelve a procesar", b["procesados"] == 1, b)

s, b = req("POST", "/ia/clave", {"api_key": ""}, admin)
estado = req("GET", "/ia/estado", token=sec)[1]
check("quitar la clave", s == 200 and b["clave_fin"] == "" and not estado["configurada"], (b, estado))

servidor.shutdown()
print(f"\n{'Todo bien' if not fallas else f'{fallas} fallas'}")
