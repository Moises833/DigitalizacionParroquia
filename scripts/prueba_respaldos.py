"""Pruebas de los respaldos (Fase 7), con una nube S3 simulada: no sale nada a internet.

Uso: después de prueba_api.py (usa sus usuarios), con el servidor iniciado así:
    $env:RESPALDO_SIN_CRON=1; $env:IA_SIN_CRON=1; .\\pocketbase.exe serve
Prueba también instalador/restaurar.ps1 en una carpeta temporal.
"""
import json
import os
import re
import shutil
import sqlite3
import struct
import subprocess
import tempfile
import threading
import time
import urllib.error
import urllib.parse
import urllib.request
import uuid
import zlib
from email.utils import formatdate
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from xml.sax.saxutils import escape

RAIZ = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SERVIDOR = os.path.join(RAIZ, "servidor")
B = "http://127.0.0.1:8090/api"
PUERTO_S3 = 8098
BUCKET = "parroquia"
CLAVE_ID = "clave-id-prueba"
fallas = 0


# --- Nube S3 simulada (solo lo que usa PocketBase) -----------------------------

class S3Falso(BaseHTTPRequestHandler):
    objetos = {}   # clave -> bytes
    partes = {}    # uploadId -> {número: bytes}
    multipartes = 0

    def log_message(self, *args):
        pass

    def responder(self, codigo, cuerpo=b"", cabeceras=None):
        if isinstance(cuerpo, str):
            cuerpo = cuerpo.encode()
        self.send_response(codigo)
        for k, v in (cabeceras or {}).items():
            self.send_header(k, v)
        self.send_header("Content-Length", str(len(cuerpo)))
        self.end_headers()
        if self.command != "HEAD":
            self.wfile.write(cuerpo)

    def error(self, codigo, nombre):
        self.responder(codigo, f'<?xml version="1.0"?><Error><Code>{nombre}</Code><Message>{nombre}</Message></Error>',
                       {"Content-Type": "application/xml"})

    def cuerpo(self):
        if self.headers.get("Transfer-Encoding", "").lower() == "chunked":
            datos = b""
            while True:
                tam = int(self.rfile.readline().split(b";")[0].strip(), 16)
                if tam == 0:
                    self.rfile.readline()
                    break
                datos += self.rfile.read(tam)
                self.rfile.readline()
        else:
            datos = self.rfile.read(int(self.headers.get("Content-Length") or 0))
        # Cargas firmadas por trozos (aws-chunked)
        if self.headers.get("x-amz-content-sha256", "").startswith("STREAMING-"):
            limpio, i = b"", 0
            while i < len(datos):
                fin = datos.index(b"\r\n", i)
                tam = int(datos[i:fin].split(b";")[0], 16)
                if tam == 0:
                    break
                limpio += datos[fin + 2:fin + 2 + tam]
                i = fin + 2 + tam + 2
            datos = limpio
        return datos

    def ruta(self):
        u = urllib.parse.urlparse(self.path)
        partes = urllib.parse.unquote(u.path).lstrip("/").split("/", 1)
        return partes[0], (partes[1] if len(partes) > 1 else ""), urllib.parse.parse_qs(u.query, keep_blank_values=True)

    def permitido(self, bucket):
        if f"Credential={CLAVE_ID}/" not in self.headers.get("Authorization", ""):
            self.error(403, "InvalidAccessKeyId")
            return False
        if bucket != BUCKET:
            self.error(404, "NoSuchBucket")
            return False
        return True

    def do_PUT(self):
        bucket, clave, q = self.ruta()
        datos = self.cuerpo()
        if not self.permitido(bucket):
            return
        if "uploadId" in q:
            self.partes.setdefault(q["uploadId"][0], {})[int(q["partNumber"][0])] = datos
        else:
            self.objetos[clave] = datos
        self.responder(200, b"", {"ETag": f'"{zlib.crc32(datos):x}"'})

    def do_POST(self):
        bucket, clave, q = self.ruta()
        self.cuerpo()
        if not self.permitido(bucket):
            return
        if "uploads" in q:
            id_ = uuid.uuid4().hex
            self.partes[id_] = {}
            self.responder(200, f'<?xml version="1.0"?><InitiateMultipartUploadResult><Bucket>{bucket}</Bucket>'
                                f'<Key>{escape(clave)}</Key><UploadId>{id_}</UploadId></InitiateMultipartUploadResult>',
                           {"Content-Type": "application/xml"})
        elif "uploadId" in q:
            partes = self.partes.pop(q["uploadId"][0])
            self.objetos[clave] = b"".join(partes[n] for n in sorted(partes))
            S3Falso.multipartes += 1
            self.responder(200, f'<?xml version="1.0"?><CompleteMultipartUploadResult><Bucket>{bucket}</Bucket>'
                                f'<Key>{escape(clave)}</Key><ETag>"x"</ETag></CompleteMultipartUploadResult>',
                           {"Content-Type": "application/xml"})
        else:
            self.error(400, "InvalidRequest")

    def do_GET(self):
        bucket, clave, q = self.ruta()
        if not self.permitido(bucket):
            return
        if not clave:
            prefijo = q.get("prefix", [""])[0]
            claves = sorted(k for k in self.objetos if k.startswith(prefijo))
            contenido = "".join(
                f"<Contents><Key>{escape(k)}</Key><LastModified>2026-10-22T12:00:00.000Z</LastModified>"
                f'<ETag>"x"</ETag><Size>{len(self.objetos[k])}</Size><StorageClass>STANDARD</StorageClass></Contents>'
                for k in claves)
            self.responder(200, f'<?xml version="1.0" encoding="UTF-8"?><ListBucketResult xmlns="http://s3.amazonaws.com/doc/2006-03-01/">'
                                f"<Name>{bucket}</Name><Prefix>{escape(prefijo)}</Prefix><KeyCount>{len(claves)}</KeyCount>"
                                f"<MaxKeys>1000</MaxKeys><IsTruncated>false</IsTruncated>{contenido}</ListBucketResult>",
                           {"Content-Type": "application/xml"})
            return
        if clave not in self.objetos:
            self.error(404, "NoSuchKey")
            return
        self.responder(200, self.objetos[clave], {"Content-Type": "application/octet-stream", "ETag": '"x"',
                                                  "Last-Modified": formatdate(usegmt=True)})

    def do_HEAD(self):
        bucket, clave, _ = self.ruta()
        if not self.permitido(bucket):
            return
        if clave not in self.objetos:
            self.error(404, "NoSuchKey")
            return
        self.send_response(200)
        self.send_header("Content-Length", str(len(self.objetos[clave])))
        self.send_header("Content-Type", "application/octet-stream")
        self.send_header("ETag", '"x"')
        self.send_header("Last-Modified", formatdate(usegmt=True))
        self.end_headers()

    def do_DELETE(self):
        bucket, clave, q = self.ruta()
        if not self.permitido(bucket):
            return
        if "uploadId" in q:
            self.partes.pop(q["uploadId"][0], None)
        else:
            self.objetos.pop(clave, None)
        self.responder(204)


# --- Utilidades -----------------------------------------------------------------

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


def png(ancho, alto, ruido=False):
    def trozo(tipo, datos):
        return struct.pack(">I", len(datos)) + tipo + datos + struct.pack(">I", zlib.crc32(tipo + datos) & 0xFFFFFFFF)
    filas = b"".join(b"\x00" + (os.urandom(ancho * 3) if ruido else b"\xee\xdd\xcc" * ancho) for _ in range(alto))
    return (b"\x89PNG\r\n\x1a\n" + trozo(b"IHDR", struct.pack(">IIBBBBB", ancho, alto, 8, 2, 0, 0, 0))
            + trozo(b"IDAT", zlib.compress(filas, 0 if ruido else 6)) + trozo(b"IEND", b""))


def subir_pagina(libro, folio, imagen, token):
    limite = uuid.uuid4().hex
    partes = [f'--{limite}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode()
              for k, v in {"libro": libro, "folio": folio, "estado": "pendiente", "origen": "pc"}.items()]
    partes.append(f'--{limite}\r\nContent-Disposition: form-data; name="imagen"; filename="p.png"\r\n'
                  f'Content-Type: image/png\r\n\r\n'.encode() + imagen + b"\r\n" + f"--{limite}--\r\n".encode())
    r = urllib.request.Request(B + "/collections/paginas/records", data=b"".join(partes), method="POST")
    r.add_header("Content-Type", f"multipart/form-data; boundary={limite}")
    r.add_header("Authorization", token)
    with urllib.request.urlopen(r) as resp:
        return json.loads(resp.read())


def check(nombre, cond, extra=""):
    global fallas
    fallas += 0 if cond else 1
    print(("OK   " if cond else "FALLA") + " " + nombre + (" -> " + str(extra)[:400] if not cond else ""))


def login(email):
    return req("POST", "/collections/users/auth-with-password", {"identity": email, "password": "clave-prueba-123"})[1]["token"]


def respaldar(token):
    s, b = req("POST", "/respaldos/ejecutar", token=token)
    return {r["destino"]: r for r in b.get("resultados", [])} if s == 200 else {"_error": (s, b)}


def copias_db(carpeta):
    d = os.path.join(carpeta, "db")
    return sorted(f for f in os.listdir(d) if re.match(r"datos_[\d_-]+\.db$", f)) if os.path.isdir(d) else []


def archivos(carpeta):
    lista = []
    for raiz, _, nombres in os.walk(os.path.join(carpeta, "storage")):
        lista += [os.path.join(raiz, n) for n in nombres if not n.endswith(".attrs")]
    return lista


# --- Pruebas -----------------------------------------------------------------------

servidor_s3 = ThreadingHTTPServer(("127.0.0.1", PUERTO_S3), S3Falso)
threading.Thread(target=servidor_s3.serve_forever, daemon=True).start()
temporales = []

try:
    admin, sec = login("admin@prueba.test"), login("sec@prueba.test")
    cfg = req("GET", "/collections/config_respaldo/records", token=admin)[1]["items"][0]
    cid = cfg["id"]
    check("valores por defecto: carpeta activa, cada 8 horas, 30 copias",
          cfg["carpeta_activa"] and cfg["cada_horas"] == 8 and cfg["conservar"] == 30 and not cfg["nube_activa"], cfg)

    check("secretaría no ve la configuración", req("GET", "/collections/config_respaldo/records", token=sec)[1].get("items") == [])
    check("secretaría no puede respaldar", req("POST", "/respaldos/ejecutar", token=sec)[0] == 403)
    s, est = req("GET", "/respaldos/estado", token=sec)
    check("secretaría ve el estado, sin la ruta", s == 200 and "ultimo_ok" in est["carpeta"] and "ruta" not in est["carpeta"], est)
    check("sin sesión no hay estado", req("GET", "/respaldos/estado")[0] in (401, 403))
    check("la carpeta debe ser una ruta completa",
          req("PATCH", f"/collections/config_respaldo/records/{cid}", {"carpeta_ruta": "respaldos"}, admin)[0] == 400)

    carpeta = tempfile.mkdtemp(prefix="respaldo_prueba_")
    temporales.append(carpeta)
    s, cfg = req("PATCH", f"/collections/config_respaldo/records/{cid}",
                 {"carpeta_ruta": carpeta, "conservar": 2, "carpeta_error": "inventado", "en_curso": "x"}, admin)
    check("guardar carpeta; el estado no se puede escribir",
          s == 200 and cfg["carpeta_ruta"] == carpeta and cfg["carpeta_error"] == "" and cfg["en_curso"] == "", cfg)
    s, b = req("POST", "/respaldos/probar/carpeta", token=admin)
    check("probar la carpeta", s == 200 and b.get("ok"), b)

    sufijo = uuid.uuid4().hex[:6]
    libro = req("POST", "/collections/libros/records", {"numero": f"Libro Resp {sufijo}", "sacramento": "bautismo"}, sec)[1]["id"]
    imagen = png(30, 30)
    pagina = subir_pagina(libro, "1", imagen, sec)
    clave_pagina = f"{pagina['collectionId']}/{pagina['id']}/{pagina['imagen']}"

    r = respaldar(admin)
    rc = r.get("carpeta", {})
    check("primer respaldo a la carpeta", rc.get("estado") == "ok" and rc.get("copiados", 0) >= 1, r)
    copia = os.path.join(carpeta, "storage", *clave_pagina.split("/"))
    check("la imagen de la página está en el respaldo", os.path.isfile(copia) and open(copia, "rb").read() == imagen, copia)
    check("hay una copia de la base", len(copias_db(carpeta)) == 1, copias_db(carpeta))
    con = sqlite3.connect(os.path.join(carpeta, "db", copias_db(carpeta)[0]))
    check("la copia de la base se puede abrir y tiene la página",
          con.execute("SELECT COUNT(*) FROM paginas WHERE id = ?", (pagina["id"],)).fetchone()[0] == 1)
    con.close()
    check("no se copian las miniaturas", not any("thumbs_" in f for f in archivos(carpeta)))

    time.sleep(1.1)
    r = respaldar(admin)
    check("el segundo respaldo solo copia lo nuevo (nada)", r.get("carpeta", {}).get("copiados") == 0, r)
    time.sleep(1.1)
    respaldar(admin)
    check("se conservan solo las últimas 2 copias de la base", len(copias_db(carpeta)) == 2, copias_db(carpeta))

    s, b = req("GET", "/respaldos/copias/carpeta", token=admin)
    check("lista de copias de la base", s == 200 and len(b) == 2 and b[0]["nombre"] > b[1]["nombre"], b)
    s, hist = req("GET", "/collections/respaldos/records?sort=-created", token=admin)
    check("historial de respaldos", s == 200 and hist["totalItems"] >= 3 and hist["items"][0]["destino"] == "carpeta", hist)
    check("secretaría no ve el historial", req("GET", "/collections/respaldos/records", token=sec)[1].get("items") == [])

    # Disco desconectado
    req("PATCH", f"/collections/config_respaldo/records/{cid}", {"carpeta_ruta": "Q:\\NoExiste\\Respaldos"}, admin)
    s, b = req("POST", "/respaldos/probar/carpeta", token=admin)
    check("probar una carpeta de un disco no conectado avisa", s == 400 and "conectado" in b.get("message", ""), b)
    r = respaldar(admin)
    check("el respaldo a un disco no conectado queda con error", r.get("carpeta", {}).get("estado") == "error", r)
    s, est = req("GET", "/respaldos/estado", token=admin)
    check("el estado muestra el error", "conectado" in est["carpeta"]["error"], est)
    req("PATCH", f"/collections/config_respaldo/records/{cid}", {"carpeta_ruta": carpeta}, admin)

    # Nube
    s, cfg = req("PATCH", f"/collections/config_respaldo/records/{cid}", {
        "nube_activa": True, "s3_endpoint": f"http://127.0.0.1:{PUERTO_S3}", "s3_region": "us-east-1",
        "s3_bucket": BUCKET, "s3_prefijo": "bautismos", "s3_clave_id": CLAVE_ID}, admin)
    check("guardar datos de la nube", s == 200, cfg)
    s, b = req("POST", "/respaldos/secreto", {"secreto": "secreto-de-prueba-1234"}, admin)
    check("guardar la clave secreta", s == 200 and b.get("secreto_fin") == "1234", b)
    check("secretaría no puede cambiar la clave", req("POST", "/respaldos/secreto", {"secreto": "x"}, sec)[0] == 403)
    cfg = req("GET", f"/collections/config_respaldo/records/{cid}", token=admin)[1]
    check("la clave secreta nunca se devuelve", "s3_secreto" not in cfg and cfg["secreto_fin"] == "1234", cfg)

    s, b = req("POST", "/respaldos/probar/nube", token=admin)
    check("probar la nube", s == 200 and b.get("ok") and not any("prueba_conexion" in k for k in S3Falso.objetos), b)

    grande = png(1500, 1400, ruido=True)  # ~6 MB: se sube en partes
    subir_pagina(libro, "2", grande, sec)
    r = respaldar(admin)
    check("respaldo a la nube", r.get("nube", {}).get("estado") == "ok", r)
    check("la carpeta también se actualizó", r.get("carpeta", {}).get("copiados") == 1, r.get("carpeta"))
    en_nube = {k: v for k, v in S3Falso.objetos.items() if k.startswith("bautismos/storage/")}
    check("en la nube están las mismas imágenes", len(en_nube) == len(archivos(carpeta)), (len(en_nube), len(archivos(carpeta))))
    check("la imagen llegó intacta", S3Falso.objetos.get(f"bautismos/storage/{clave_pagina}") == imagen)
    check("la imagen grande llegó completa", grande in en_nube.values() and S3Falso.multipartes >= 1,
          (len(grande), sorted(len(v) for v in en_nube.values())[-1], S3Falso.multipartes))
    check("hay copia de la base en la nube", any(re.match(r"bautismos/db/datos_.*\.db$", k) for k in S3Falso.objetos))

    time.sleep(1.1)
    r = respaldar(admin)
    check("la nube también es incremental", r.get("nube", {}).get("copiados") == 0, r)

    s, cfg = req("PATCH", f"/collections/config_respaldo/records/{cid}", {"s3_clave_id": "otra-clave"}, admin)
    s, b = req("POST", "/respaldos/probar/nube", token=admin)
    check("clave equivocada: mensaje claro", s == 400 and "rechazó" in b.get("message", ""), b)
    r = respaldar(admin)
    check("clave equivocada: respaldo con error", r.get("nube", {}).get("estado") == "error", r)
    req("PATCH", f"/collections/config_respaldo/records/{cid}", {"s3_clave_id": CLAVE_ID}, admin)

    # Restaurar desde la nube en una PC nueva: datos vacíos y la nube por variables
    bajado = tempfile.mkdtemp(prefix="respaldo_bajado_")
    pc_nueva = tempfile.mkdtemp(prefix="pc_nueva_")
    temporales += [bajado, pc_nueva]
    entorno = {**os.environ, "RESPALDO_SIN_CRON": "1", "IA_SIN_CRON": "1"}
    nube = {"RESPALDO_S3_ENDPOINT": f"http://127.0.0.1:{PUERTO_S3}", "RESPALDO_S3_REGION": "us-east-1",
            "RESPALDO_S3_BUCKET": BUCKET, "RESPALDO_S3_PREFIJO": "bautismos",
            "RESPALDO_S3_CLAVE_ID": CLAVE_ID, "RESPALDO_S3_SECRETO": "secreto-de-prueba-1234"}
    p = subprocess.run([os.path.join(SERVIDOR, "pocketbase.exe"), "descargar-respaldo", bajado,
                        f"--dir={os.path.join(pc_nueva, 'pb_data')}", "--hooksDir=pb_hooks"],
                       cwd=SERVIDOR, env={**entorno, **nube}, capture_output=True, text=True, timeout=300)
    check("descargar-respaldo baja la base y las imágenes",
          p.returncode == 0 and len(copias_db(bajado)) == 1 and len(archivos(bajado)) == len(en_nube),
          (p.returncode, p.stdout[-300:], p.stderr[-300:], copias_db(bajado), len(archivos(bajado))))

    # restaurar.ps1 en una instalación de prueba
    sistema = tempfile.mkdtemp(prefix="sistema_prueba_")
    temporales.append(sistema)
    shutil.copy(os.path.join(SERVIDOR, "pocketbase.exe"), sistema)
    for d in ("pb_hooks", "pb_migrations"):
        shutil.copytree(os.path.join(SERVIDOR, d), os.path.join(sistema, d))
    os.makedirs(os.path.join(sistema, "pb_data"))
    open(os.path.join(sistema, "pb_data", "anterior.txt"), "w").write("datos anteriores")
    p = subprocess.run(["powershell", "-NoProfile", "-ExecutionPolicy", "Bypass", "-File",
                        os.path.join(RAIZ, "instalador", "restaurar.ps1"),
                        "-Carpeta", sistema, "-Origen", bajado, "-Confirmar", "-Puerto", "8091"],
                       env=entorno, capture_output=True, text=True, timeout=300)
    datos = os.path.join(sistema, "pb_data")
    antes = [d for d in os.listdir(sistema) if d.startswith("pb_data_antes_")]
    check("restaurar.ps1 termina bien", p.returncode == 0, (p.stdout[-500:], p.stderr[-500:]))
    check("los datos anteriores se guardan aparte",
          len(antes) == 1 and os.path.isfile(os.path.join(sistema, antes[0], "anterior.txt")), antes)
    restaurada = os.path.join(datos, "storage", *clave_pagina.split("/"))
    check("las imágenes quedan restauradas", os.path.isfile(restaurada) and open(restaurada, "rb").read() == imagen)
    con = sqlite3.connect(os.path.join(datos, "data.db"))
    check("la base restaurada tiene la página",
          con.execute("SELECT COUNT(*) FROM paginas WHERE id = ?", (pagina["id"],)).fetchone()[0] == 1)
    con.close()
finally:
    # Deja la configuración como al instalar (la carpeta temporal se borra).
    try:
        req("PATCH", f"/collections/config_respaldo/records/{cid}",
            {"nube_activa": False, "carpeta_ruta": "", "conservar": 30}, admin)
    except NameError:
        pass
    servidor_s3.shutdown()
    for t in temporales:
        shutil.rmtree(t, ignore_errors=True)

print(f"\n{'Todo bien' if not fallas else f'{fallas} fallas'}")
