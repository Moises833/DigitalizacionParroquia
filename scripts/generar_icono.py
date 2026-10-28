"""Genera instalador/sistema.ico, el ícono de los accesos directos (libro con cruz).

Uso: python scripts/generar_icono.py   (requiere Pillow)
"""
from pathlib import Path

from PIL import Image, ImageDraw

PRIMARIO = (107, 39, 55, 255)   # el mismo color de la interfaz
PAPEL = (246, 240, 226, 255)
DORADO = (217, 180, 90, 255)

L = 256
img = Image.new("RGBA", (L, L), (0, 0, 0, 0))
d = ImageDraw.Draw(img)
d.rounded_rectangle((8, 8, L - 8, L - 8), radius=48, fill=PRIMARIO)
# libro abierto
d.polygon([(40, 78), (124, 92), (124, 208), (40, 194)], fill=PAPEL)
d.polygon([(216, 78), (132, 92), (132, 208), (216, 194)], fill=PAPEL)
# cruz sobre la página derecha
d.rectangle((168, 108, 180, 172), fill=PRIMARIO)
d.rectangle((150, 126, 198, 138), fill=PRIMARIO)
# renglones de la página izquierda
for y in (118, 140, 162):
    d.line((56, y, 110, y + 8), fill=(170, 150, 120, 255), width=6)
# cinta
d.polygon([(120, 196), (136, 196), (136, 236), (128, 226), (120, 236)], fill=DORADO)

destino = Path(__file__).resolve().parent.parent / "instalador" / "sistema.ico"
img.save(destino, sizes=[(16, 16), (24, 24), (32, 32), (48, 48), (64, 64), (128, 128), (256, 256)])
print(destino)
