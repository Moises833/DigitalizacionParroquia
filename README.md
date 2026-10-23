# Digitalización de Libros de Bautismo

Sistema para digitalizar los libros de bautismo de la parroquia: captura de páginas (también desde el teléfono), separación de las actas de cada página, transcripción asistida por IA, búsqueda y emisión de la fe de bautismo.

Está pensado para equipos modestos: todo corre en **un solo ejecutable** (PocketBase, unos 35 MB) con base de datos SQLite local, sin instalar PHP, Node ni bases de datos en la parroquia.

## Estructura

```
servidor/
  pb_migrations/   Esquema de la base de datos (colecciones, reglas de acceso, índices)
  pb_hooks/        Lógica del servidor en JavaScript (auditoría, búsqueda, configuración inicial)
  pb_public/       Interfaz compilada (se genera con npm run build, no se versiona)
web/               Interfaz en Svelte 5 + Vite
instalador/        Scripts que se copian al equipo de la parroquia
scripts/           Herramientas de desarrollo (empaquetado, descarga verificada de PocketBase)
```

## Desarrollo

Requisitos: Windows 64 bits, Node.js 20 o superior.

```powershell
# 1. Descargar PocketBase (versión fijada, verificada por checksum)
powershell -ExecutionPolicy Bypass -Command ". .\scripts\pocketbase.ps1; Obtener-PocketBase servidor"

# 2. Iniciar el servidor (aplica las migraciones automáticamente)
cd servidor; .\pocketbase.exe serve

# 3. En otra terminal, la interfaz con recarga en caliente
cd web; npm install; npm run dev
```

La interfaz queda en http://localhost:5173 (Vite reenvía `/api` al puerto 8090). La primera vez pide registrar la parroquia y el administrador.

Pruebas de la API: con el servidor recién creado (sin `pb_data`), ejecutar en orden `python scripts/prueba_api.py` (reglas de acceso, auditoría, búsqueda), `python scripts/prueba_captura.py` (vinculación del teléfono, folios, archivos protegidos) y `python scripts/prueba_actas.py` (recuadros, estado de las páginas, quién creó y revisó cada acta). Al final, `python scripts/prueba_ia.py` (cola de IA contra un Gemini simulado: no sale nada a internet); para esta, el servidor debe iniciarse con `IA_URL_BASE=http://127.0.0.1:8099 IA_SIN_CRON=1`. La búsqueda se prueba con `python scripts/prueba_busqueda.py` (después de `prueba_api.py`).

Pruebas de la interfaz (fechas en letras, recuadros): `cd web; npm test`.

Para probar el teléfono en desarrollo, iniciar el servidor con `.\pocketbase.exe serve --http=0.0.0.0:8090` y abrir la interfaz compilada en el puerto 8090 (el QR apunta a ese puerto).

## Captura de páginas

- **Desde la PC:** se arrastran muchas fotos a la vez; se numeran por orden de nombre de archivo a partir del siguiente folio libre, y cada folio se puede corregir antes de subir.
- **Desde el teléfono:** la PC muestra un QR con un enlace de un solo uso (vence a las 8 horas o al terminar la vinculación). El teléfono no necesita cuenta; cada foto llega a la PC al instante con el siguiente folio libre.
- Las fotos se comprimen en el navegador (WebP, lado mayor de 2400 px) y se genera una miniatura de ~20 KB, así la computadora de la parroquia no procesa imágenes.
- Las imágenes son archivos protegidos: solo se descargan con la sesión iniciada.

## Transcripción de actas

En **Actas** se elige el libro y se ve qué páginas faltan; **Continuar donde quedé** abre la primera sin terminar.

1. **Separar las actas:** se elige cuántas actas tiene la página (crea franjas iguales) o se dibuja cada recuadro arrastrando sobre la imagen. Los recuadros se mueven y se estiran con el mouse o con el teclado (flechas; Mayúsculas + flechas cambia el tamaño). Se guardan solos.
2. **Transcribir:** el formulario muestra el recorte del acta (con zoom) arriba de los campos. Las fechas se escriben como figuran en el libro, "veintiuno de mayo de mil novecientos ochenta y uno" o "21/05/1981", y se guardan como 1981-05-21; se rechazan fechas imposibles y años de dos cifras.
3. El número de acta, el ministro, la fecha de bautizo y el lugar se copian del acta anterior resaltados en amarillo para verificarlos. **Ctrl + Enter** guarda y pasa a la siguiente acta.
4. Cada acta guarda su propio recorte de imagen; si después se ajusta el recuadro, el recorte se rehace.

Estados de la página: *Sin separar* → *Por transcribir* → *Completa* (todas sus actas guardadas). Un acta queda como *Borrador* hasta que se marca **Revisada**; el sistema registra quién la creó y quién la revisó.

## Lectura con inteligencia artificial

La IA (Google Gemini) lee cada acta recortada y propone los datos; **una persona siempre los revisa** antes de guardarlos. Es opcional: sin ella todo se transcribe a mano igual.

1. **Configurar (administrador):** en *Inteligencia artificial*, pegar una clave de Gemini (gratis en aistudio.google.com con una cuenta de Google de la parroquia), pulsar *Probar conexión*, elegir un modelo «flash» y activar la IA. La clave queda solo en el servidor y nunca se muestra.
2. **Enviar:** en la página separada, *Leer con IA* manda las actas que falten; en *Actas*, *Leer con IA las páginas separadas* manda el libro entero.
3. **Cola:** el servidor envía unas pocas por minuto (8 por defecto, con un máximo diario) mientras haya internet. Si Google avisa que se llegó al límite, espera y sigue solo; tras 3 errores un acta queda marcada y se puede reintentar.
4. **Revisar:** al abrir el acta, el formulario ya viene lleno. En amarillo lo leído por la IA; en rojo lo que no leyó con seguridad. Las fechas se vuelven a calcular desde el texto que leyó la IA, y se marcan si no coinciden con su conversión. Al guardar, el acta queda como de origen «ia» y se conserva lo que propuso la IA.

**Privacidad:** en el plan gratuito, Google puede usar lo enviado para mejorar sus productos y personas de Google pueden revisarlo. Las actas tienen datos personales: la parroquia debe aprobarlo o activar la facturación de Google Cloud, con la que Google no usa los datos.

Para cambiar de proveedor de IA más adelante basta con reemplazar `pedirGemini()` en `servidor/pb_hooks/ia.js`.

## Búsqueda

En **Buscar** los resultados aparecen mientras se escribe. Se puede combinar:

- **Nombre del bautizado:** nombres o apellidos, en cualquier orden y sin importar acentos ni mayúsculas ("nunez maria" encuentra a María José Núñez Ávila).
- **Padres** y **Padrinos:** buscan solo en esos campos, así el nombre de una madrina no aparece como bautizada.
- **Libro (tomo)**, **Año de bautizo** (un año, "1981", o un rango, "1980-1985"), **Folio** y **N.º de acta**.

Cada resultado muestra dónde está (libro, folio, número de acta) y al hacer clic abre el acta junto a la imagen de la página. **Volver a la búsqueda** conserva los filtros. Las actas sin revisar llevan la marca *Sin revisar*.

## Instalar en la parroquia

1. En el equipo de desarrollo: `powershell -ExecutionPolicy Bypass -File scripts\empaquetar.ps1`
2. Copiar `dist\SistemaBautismos.zip` al equipo de la parroquia y descomprimirlo en una ruta corta (por ejemplo `C:\SistemaBautismos`).
3. Ejecutar `instalar_sistema.bat` una sola vez: crea la cuenta de mantenimiento, abre el puerto 8090 solo para la red local y crea los accesos directos.
4. Abrir **Sistema de Bautismos** desde el Escritorio.

Para **actualizar**, reemplazar todo excepto la carpeta `pb_data` (ahí están los datos y las imágenes).

## Roles

| Rol | Puede |
|---|---|
| Administrador | Todo: usuarios, datos de la parroquia, eliminar registros, ver la auditoría |
| Secretaría | Registrar y editar libros, páginas y actas |

Cada creación, edición o eliminación queda registrada en la colección `auditoria` con el usuario y los valores anteriores y nuevos.

## Plan de trabajo

| Fase | Contenido | Estado |
|---|---|---|
| 1. Base | Esquema, usuarios y roles, auditoría, interfaz, instalador | ✅ |
| 2. Captura | Subida de varias fotos desde la PC y desde el teléfono (QR), compresión en el navegador | ✅ |
| 3. Segmentación y captura manual | Separar las actas de cada página; formulario asistido | ✅ |
| 4. IA | Cola de transcripción con Gemini (proveedor intercambiable) y revisión | ✅ (falta probar con una clave real) |
| 5. Búsqueda | Filtros por nombre, padres, padrinos, libro, año y folio | ✅ |
| 6. Fe de bautismo | Certificado con membrete y sello, listo para imprimir | Pendiente |
| 7. Respaldos | Respaldo local automático y copia en la nube | Pendiente |
| 8. Piloto | Prueba con un libro real en la parroquia | Pendiente |
