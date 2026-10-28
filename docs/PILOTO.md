# Guía del piloto

Probar el sistema con **un libro real**, en la computadora de la parroquia y con la persona que lo va a usar, durante 2 o 3 semanas. Al final se decide, con datos, si se sigue con los demás libros.

Esta guía es para quien instala y acompaña el piloto. La persona de la secretaría tiene su propia guía dentro del sistema: menú **Ayuda**.

## 1. Qué se quiere saber

| Pregunta | Cómo se mide | Meta orientativa |
|---|---|---|
| ¿La secretaría puede trabajar sola? | Observaciones anotadas y si necesitó ayuda después del segundo día | Sin ayuda desde el día 3 |
| ¿Cuánto tarda cada acta? | *Informe del piloto* → Ritmo de trabajo (actas por hora) | A comparar: a mano vs. con IA |
| ¿Sirve la IA? | *Informe del piloto* → Acierto de la IA | Más del 80 % de los datos sin cambios |
| ¿Se encuentra un acta rápido? | Pedirle que busque 5 actas al azar del libro, con cronómetro | Menos de 1 minuto cada una |
| ¿La fe de bautismo sirve? | Imprimir una y que el párroco la apruebe | Aprobada sin cambios de formato |
| ¿Los datos están a salvo? | Respaldos al día y una restauración de prueba hecha | Restauración probada |
| ¿Hay errores graves? | *Informe del piloto* → Observaciones | Ninguna pendiente al cerrar |

## 2. Antes de ir: lo que decide la parroquia

Conviene llevar estas decisiones resueltas (o resolverlas el primer día con el párroco):

1. **Qué libro.** Uno con letra legible, de 100 a 300 actas, que se consulte seguido (por ejemplo, de 1980 a 2000). Evitar el más antiguo o el más deteriorado para el piloto.
2. **La IA (Gemini gratis): aprobada.** La parroquia autorizó usar el plan gratuito sabiendo que Google puede usar lo que se le envía y que personas de Google pueden revisarlo. Hace falta una **cuenta de Google de la parroquia** (no una personal) para crear la clave. Si más adelante se quiere que Google no use los datos, basta con activar la facturación de Google Cloud en esa cuenta: la clave sigue siendo la misma.
3. **La copia en la nube.** Una copia de todas las actas e imágenes en un servicio externo (por ejemplo Backblaze B2, 10 GB gratis). Si se aprueba: cuenta con el correo de la parroquia.
4. **Disco externo** para el respaldo local (si la parroquia no tiene uno, llevarlo): conectado siempre a la PC.
5. **Quién participa.** Quién carga (secretaría) y quién es administrador (párroco o quien él designe).
6. **El texto de la fe de bautismo.** Llevar o pedir un certificado de los que se entregan hoy, para comparar la redacción.

## 3. Qué llevar

- Pendrive con `SistemaBautismos.zip` ([descargar la última versión](https://github.com/Moises833/DigitalizacionParroquia/releases/latest/download/SistemaBautismos.zip)), por si la parroquia no tiene internet, y esta guía.
- Disco externo, si hace falta.
- El membrete, el sello y la firma del párroco **escaneados o fotografiados** sobre papel blanco, con buena luz (mejor PNG con fondo transparente).
- Datos de la parroquia: nombre, diócesis, dirección, ciudad, teléfono y párroco.
- Para las fotos de las páginas: teléfono con buena cámara, un lugar con luz pareja (sin sol directo), y si es posible un soporte o trípode.

## 4. Día 1: instalación (alrededor de 1 hora)

1. **Revisar la computadora:** Windows de 64 bits (Configuración → Sistema → Información), al menos 10 GB libres, y que el teléfono y la PC estén en la misma red wifi si se van a sacar fotos con el teléfono.
2. Copiar el zip a la PC (o descargarlo ahí), clic derecho → *Extraer todo…* en cualquier carpeta.
3. Doble clic en `instalar_sistema.bat` y responder con **Enter** a todo. Si Windows avisa «Windows protegió su PC»: *Más información* → *Ejecutar de todas formas*.
   - Copia el sistema a `C:\SistemaBautismos`.
   - Crea la **cuenta de mantenimiento** y muestra su contraseña: anotarla en la hoja del final y guardarla fuera de la PC. Es para soporte técnico, no para el uso diario.
   - Pide la **clave de Gemini**: tenerla creada antes (paso 8) y pegarla con clic derecho; se ve con asteriscos. El instalador la prueba, elige el modelo y deja la IA activada. Con Enter se omite y se carga después.
   - Pide permiso de administrador de Windows para abrir el puerto 8090 solo a la red local (para el teléfono): responder **Sí**.
   - Deja el sistema arrancando solo con Windows y lo abre.
4. La primera vez el sistema pide el nombre de la parroquia y crea el **administrador**. Después se abre con **Sistema de Bautismos** del Escritorio (ícono de libro con cruz): aparece en su propia ventana, como un programa, y no hay ninguna ventana negra que cuidar. La carpeta descargada y el zip se pueden borrar.
5. **Parroquia:** completar los datos y cargar membrete, sello y firma.
6. **Usuarios:** crear la cuenta de la secretaría (rol *Secretaría*).
7. **Respaldos:** en *Carpeta*, poner la ruta del disco externo (por ejemplo `E:\Respaldos Bautismos`), *Guardar*, *Probar* y *Respaldar ahora*. Si se aprobó la nube, cargar sus datos y *Probar*.
8. **Inteligencia artificial:** la clave se crea con la cuenta de Google de la parroquia en https://aistudio.google.com/apikey (*Create API key*). Si se pegó en el instalador, en *Inteligencia artificial* ya figura activada: pulsar *Probar conexión* para confirmarlo. Si no, pegarla ahí → *Guardar clave* → *Probar conexión* (si el modelo configurado no responde, cambia solo a otro «flash») → marcar *IA activada* → *Guardar ajustes*. Anotar en la hoja de datos qué cuenta se usó; la clave no hace falta anotarla (se puede crear otra).
9. **Libros:** registrar el libro del piloto (número o nombre como figura en el lomo, y años).

## 5. Día 1: capacitación con la secretaría (1 a 2 horas)

Hacerlo con el libro real, siguiendo la guía del menú **Ayuda**, y dejar que ella maneje el mouse:

1. Fotografiar 5 a 10 páginas (con el teléfono y el QR, o pasando fotos a la PC).
2. Separar las actas de esas páginas.
3. Transcribir 5 actas a mano. Si hay IA: enviar el resto y revisar 5 propuestas.
4. Marcar como *Revisada* solo lo que se comparó con el libro.
5. Buscar un acta por nombre, por padres y por año.
6. Emitir una fe de bautismo de un acta revisada e imprimirla. Revisar con el párroco el texto, los márgenes y si se usan el sello y la firma escaneados o se sella y firma a mano. Los certificados quedan numerados y no se borran: en «Para fines de», poner *Prueba del piloto*.
7. Mostrar el botón **Anotar un problema** y pedirle que lo use para cualquier cosa rara o idea, en el momento.

## 6. Durante el piloto

- **Rutina de la secretaría:** encender la PC (el sistema arranca solo), trabajar, y si Inicio muestra un aviso rojo de respaldos, avisar al administrador.
- **Fotos primero, transcripción después:** conviene fotografiar el libro completo en uno o dos días y después transcribir. Si una foto sale movida o cortada, se vuelve a sacar antes de separar las actas.
- **Una vez por semana** (en persona), revisar con el administrador:
  - *Informe del piloto*: avance, actas por hora, acierto de la IA y observaciones (responderlas y marcarlas como resueltas).
  - *Respaldos*: que el último respaldo sea reciente en todos los destinos.
- Si se pide medir a mano: cronometrar cuánto lleva fotografiar 10 páginas.

## 7. Antes de terminar: restauración de prueba

Probar que el respaldo sirve, sin riesgo para los datos:

1. En `C:\SistemaBautismos`, ejecutar `restaurar_respaldo.bat` (cierra el sistema solo), elegir la carpeta del disco externo y la copia más reciente, y escribir `SI`.
2. Abrir el sistema y comprobar que estén las últimas actas cargadas.
3. Los datos anteriores quedaron en `C:\SistemaBautismos\pb_data_antes_<fecha>`. Si todo está bien, esa carpeta se puede borrar días después.

Si hay nube, repetir eligiendo la nube (la descarga puede tardar).

## 8. Al terminar: evaluación

Reunir y llevar:

- Del *Informe del piloto*: captura de pantalla de todo el informe y el **Excel** del libro (botón «Excel»).
- Las observaciones pendientes y resueltas.
- La opinión de la secretaría y del párroco (¿lo usarían todos los días? ¿qué cambiarían?).
- La tabla de la sección 1 completa.

Con eso se decide si se sigue con los demás libros, qué ajustar antes, y si conviene la IA.

## 9. Si algo sale mal

| Qué pasa | Qué hacer |
|---|---|
| El sistema no abre | Abrir «Sistema de Bautismos» del Escritorio. Si aparece un aviso con un error, sacarle una foto. Para ver más detalle (soporte): `iniciar_sistema.bat` en `C:\SistemaBautismos` muestra el servidor en una ventana. |
| La pantalla dice «No hay conexión con el servidor» | Pulsar *Reintentar*; si sigue, cerrar esa ventana y abrir «Sistema de Bautismos» del Escritorio, que lo vuelve a iniciar. |
| El teléfono no se conecta | Misma red wifi que la PC; volver a generar el QR. Si sigue, el firewall: repetir `instalar_sistema.bat` aceptando el permiso de administrador. |
| Aparece un error al guardar | Anotarlo con «Anotar un problema» (dice en qué pantalla estaba) y sacar una foto de la pantalla. Lo ya guardado no se pierde. |
| Se cortó la luz | Lo guardado está a salvo. Al encender, el sistema arranca solo y el respaldo se pone al día. |
| Se olvidó la contraseña de la secretaría | El administrador la cambia en *Usuarios*. |
| Se olvidó la del administrador | Con la cuenta de mantenimiento, en `http://127.0.0.1:8090/_/`. |
| La IA no devuelve nada | *Inteligencia artificial* → *Estado*. Sin internet, espera y sigue sola. Si se llegó al límite diario del plan gratis, sigue al día siguiente. Si dice «saturado», es Google: reintenta sola. Si dice que la clave no sirve, crear otra en aistudio.google.com y *Probar conexión*. Mientras tanto se puede transcribir a mano. |
| Inicio avisa que no hay respaldo | *Respaldos*: revisar el error (disco desconectado, sin internet) y *Respaldar ahora*. |

## 10. Hoja de datos de la instalación

Completar a mano y dejar una copia en la parroquia, **en un lugar seguro** (no pegada a la PC):

| Dato | Valor |
|---|---|
| Fecha de instalación | |
| Carpeta del sistema | `C:\SistemaBautismos` |
| Cuenta de mantenimiento (correo) | |
| Dónde se guardó su contraseña | |
| Administrador del sistema (persona y correo) | |
| Carpeta de respaldo (disco externo) | |
| Nube: servicio, bucket y dónde se guardaron las claves | |
| IA: cuenta de Google usada | |
| Libro del piloto | |
