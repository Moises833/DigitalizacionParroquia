<script>
  import { urlArchivo } from "../lib/archivos.svelte.js"

  let { paginas, recientes, titulo, alAbrir } = $props()

  // Páginas antiguas sin miniatura propia usan la de PocketBase.
  const vista = (p) => p.miniatura ? urlArchivo(p, p.miniatura) : urlArchivo(p, p.imagen, { thumb: "400x0" })
</script>

<section>
  <h2>{titulo} <span class="suave cantidad">({paginas.length})</span></h2>
  {#if paginas.length === 0}
    <p class="suave">Todavía no hay páginas en este libro.</p>
  {:else}
    <ul class="galeria">
      {#each paginas as p (p.id)}
        <li>
          <button class="miniatura" class:reciente={recientes.has(p.id)} onclick={() => alAbrir(p)}
                  aria-label="Abrir folio {p.folio}">
            <img src={vista(p)} alt="" loading="lazy" />
            <span class="pie">
              <strong>Folio {p.folio}</strong>
              {#if p.origen === "movil"}<span class="etiqueta">Teléfono</span>{/if}
            </span>
          </button>
        </li>
      {/each}
    </ul>
  {/if}
</section>

<style>
  .cantidad { font-family: var(--fuente); font-size: 1rem; font-weight: 400; }
  .galeria {
    list-style: none; padding: 0; margin: 0;
    display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;
  }
  .miniatura {
    width: 100%; padding: 0; border: 2px solid var(--borde); border-radius: var(--radio);
    background: var(--superficie); cursor: pointer; overflow: hidden; display: flex; flex-direction: column;
  }
  .miniatura:hover, .miniatura:focus-visible { border-color: var(--primario); }
  .miniatura.reciente { border-color: var(--exito); box-shadow: 0 0 0 3px #e8f3ea; }
  img { width: 100%; aspect-ratio: 3 / 4; object-fit: cover; background: var(--fondo); display: block; }
  .pie { display: flex; justify-content: space-between; align-items: center; gap: 0.3rem; padding: 0.4rem 0.6rem; }
  .etiqueta { font-size: 0.75rem; background: var(--primario-suave); color: var(--primario); padding: 0.05rem 0.4rem; border-radius: 99px; }
</style>
