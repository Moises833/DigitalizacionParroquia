<script>
  import { arrastrar, ajustar, MAX_SEGMENTOS } from "../lib/recorte.js"

  // segmentos: [{x, y, ancho, alto}] en proporciones; su orden es la posición del acta.
  // conActa: posiciones (1..6) que ya tienen un acta guardada.
  // puedeSeleccionar(i): el padre puede impedir el cambio (p. ej. formulario sin guardar).
  let {
    src, segmentos = $bindable([]), seleccionado = $bindable(0), conActa = new Set(),
    etiquetas = new Map(), // posición -> texto breve (estado de la IA)
    puedeSeleccionar = () => true, alCambiar, alCargar,
  } = $props()

  function seleccionar(indice) {
    if (indice === seleccionado) return true
    if (!puedeSeleccionar(indice)) return false
    seleccionado = indice
    return true
  }

  const BORDES = ["n", "s", "e", "o", "ne", "no", "se", "so"]
  const PASO = 0.005

  let lienzo
  let gesto = $state.raw(null) // { indice, parte, inicial, x0, y0, rect, nuevo }

  function posicionRelativa(evento, rect) {
    return {
      x: Math.min(1, Math.max(0, (evento.clientX - rect.left) / rect.width)),
      y: Math.min(1, Math.max(0, (evento.clientY - rect.top) / rect.height)),
    }
  }

  function empezar(evento, indice, parte) {
    if (evento.button !== 0) return
    evento.preventDefault()
    evento.stopPropagation()
    if (!seleccionar(indice)) return
    const rect = lienzo.getBoundingClientRect()
    gesto = { indice, parte, inicial: { ...segmentos[indice] }, x0: evento.clientX, y0: evento.clientY, rect }
    lienzo.setPointerCapture(evento.pointerId)
  }

  // Arrastrar sobre una zona vacía dibuja un recuadro nuevo.
  function dibujar(evento) {
    if (evento.button !== 0 || segmentos.length >= MAX_SEGMENTOS) return
    evento.preventDefault()
    if (!puedeSeleccionar(segmentos.length)) return
    const rect = lienzo.getBoundingClientRect()
    const p = posicionRelativa(evento, rect)
    segmentos = [...segmentos, ajustar({ x: p.x, y: p.y, ancho: 0, alto: 0 })]
    const indice = segmentos.length - 1
    seleccionado = indice
    gesto = {
      indice, parte: "se", inicial: { x: p.x, y: p.y, ancho: 0, alto: 0 },
      x0: evento.clientX, y0: evento.clientY, rect, nuevo: true,
    }
    lienzo.setPointerCapture(evento.pointerId)
  }

  function mover(evento) {
    if (!gesto) return
    const dx = (evento.clientX - gesto.x0) / gesto.rect.width
    const dy = (evento.clientY - gesto.y0) / gesto.rect.height
    segmentos[gesto.indice] = arrastrar(gesto.inicial, gesto.parte, dx, dy)
  }

  function terminar(evento) {
    if (!gesto) return
    const { nuevo, indice, x0, y0 } = gesto
    gesto = null
    if (nuevo && Math.hypot(evento.clientX - x0, evento.clientY - y0) < 12) {
      // Fue un clic, no un recuadro: se descarta.
      segmentos = segmentos.filter((_, i) => i !== indice)
      seleccionado = Math.max(0, segmentos.length - 1)
      return
    }
    alCambiar?.()
  }

  function teclado(evento, indice) {
    const flechas = { ArrowUp: [0, -1], ArrowDown: [0, 1], ArrowLeft: [-1, 0], ArrowRight: [1, 0] }
    if (evento.key === "Enter" || evento.key === " ") {
      evento.preventDefault()
      seleccionar(indice)
      return
    }
    const d = flechas[evento.key]
    if (!d) return
    evento.preventDefault()
    if (!seleccionar(indice)) return
    // Flechas mueven; con Mayúsculas cambian el tamaño desde la esquina inferior derecha.
    const parte = evento.shiftKey ? "se" : "mover"
    segmentos[indice] = arrastrar(segmentos[indice], parte, d[0] * PASO, d[1] * PASO)
    alCambiar?.()
  }

  const estilo = (s) =>
    `left:${s.x * 100}%;top:${s.y * 100}%;width:${s.ancho * 100}%;height:${s.alto * 100}%`
</script>

<!-- svelte-ignore a11y_no_static_element_interactions -->
<div class="lienzo" class:arrastrando={gesto} bind:this={lienzo}
     onpointerdown={dibujar} onpointermove={mover} onpointerup={terminar} onpointercancel={terminar}>
  <img {src} alt="Página completa" draggable="false" onload={(e) => alCargar?.(e.currentTarget)} />

  {#each segmentos as s, i (i)}
    <div class="recuadro" class:activo={i === seleccionado} class:guardada={conActa.has(i + 1)}
         style={estilo(s)} role="button" tabindex="0"
         aria-label="Acta {i + 1}{conActa.has(i + 1) ? ', transcrita' : ''}. Flechas para mover, Mayúsculas y flechas para cambiar el tamaño."
         aria-pressed={i === seleccionado}
         onpointerdown={(e) => empezar(e, i, "mover")}
         onkeydown={(e) => teclado(e, i)}
         onclick={() => seleccionar(i)}>
      <span class="numero">{i + 1}{conActa.has(i + 1) ? " ✓" : etiquetas.get(i + 1) ? ` · ${etiquetas.get(i + 1)}` : ""}</span>
      {#if i === seleccionado}
        {#each BORDES as borde}
          <span class="asa {borde}" onpointerdown={(e) => empezar(e, i, borde)}></span>
        {/each}
      {/if}
    </div>
  {/each}
</div>

<style>
  .lienzo {
    position: relative; user-select: none; touch-action: none; cursor: crosshair;
    background: var(--fondo); border-radius: var(--radio); overflow: hidden; line-height: 0;
  }
  .lienzo.arrastrando { cursor: grabbing; }
  img { width: 100%; height: auto; display: block; pointer-events: none; }

  .recuadro {
    position: absolute; border: 2px solid #c27b17; background: rgb(194 123 23 / 0.08);
    cursor: move; line-height: 1.2;
  }
  .recuadro.guardada { border-color: var(--exito); background: rgb(47 107 58 / 0.08); }
  .recuadro.activo { border-color: var(--primario); border-width: 3px; background: rgb(107 39 55 / 0.06); z-index: 1; }
  .recuadro:focus-visible { outline: 3px solid #fff; outline-offset: -6px; }

  .numero {
    position: absolute; top: 0; left: 0; padding: 0.1rem 0.45rem;
    background: #c27b17; color: #fff; font-weight: 700; font-size: 0.9rem; border-bottom-right-radius: 6px;
  }
  .guardada .numero { background: var(--exito); }
  .activo .numero { background: var(--primario); }

  .asa {
    position: absolute; width: 14px; height: 14px; background: #fff;
    border: 2px solid var(--primario); border-radius: 3px;
  }
  .asa.n, .asa.s { left: calc(50% - 7px); cursor: ns-resize; }
  .asa.e, .asa.o { top: calc(50% - 7px); cursor: ew-resize; }
  .asa.n, .asa.ne, .asa.no { top: -8px; }
  .asa.s, .asa.se, .asa.so { bottom: -8px; }
  .asa.e, .asa.ne, .asa.se { right: -8px; }
  .asa.o, .asa.no, .asa.so { left: -8px; }
  .asa.ne, .asa.so { cursor: nesw-resize; }
  .asa.no, .asa.se { cursor: nwse-resize; }
</style>
