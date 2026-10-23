<script>
  import { untrack } from "svelte"
  import { interpretarFecha, formatearFecha } from "../lib/fechas.js"

  // valor: "YYYY-MM-DD" o "". El texto se escribe como figura en el libro
  // ("veintiuno de mayo de mil novecientos ochenta y uno", "21/05/1981").
  // invalida: el texto escrito no es una fecha; el formulario no deja guardar.
  let {
    id, etiqueta, valor = $bindable(""), copiado = $bindable(false), invalida = $bindable(false),
  } = $props()

  let texto = $state(untrack(() => formatearFecha(valor)))
  let interpretada = $derived(interpretarFecha(texto))

  // Si el valor cambia desde afuera (otra acta, copia de la anterior), se muestra.
  $effect(() => {
    const externo = valor
    untrack(() => {
      if (interpretarFecha(texto) !== externo) {
        texto = formatearFecha(externo)
        invalida = false
      }
    })
  })

  function escribir(evento) {
    texto = evento.currentTarget.value
    const r = interpretarFecha(texto)
    invalida = r === null
    if (r !== null) valor = r
    copiado = false
  }
</script>

<div class="campo" class:copiado>
  <label for={id}>{etiqueta}</label>
  <input {id} value={texto} oninput={escribir} autocomplete="off"
         aria-invalid={interpretada === null} aria-describedby="{id}-ayuda"
         placeholder="21/05/1981 o en letras" />
  <span class="ayuda" id="{id}-ayuda" class:invalida={interpretada === null}>
    {#if interpretada === null}
      No se reconoce la fecha. Escriba día, mes y año completo.
    {:else if interpretada}
      {formatearFecha(interpretada)}{copiado ? " · copiado del acta anterior, verifique" : ""}
    {:else}
      &nbsp;
    {/if}
  </span>
</div>

<style>
  .invalida { color: var(--error) !important; font-weight: 600; }
</style>
