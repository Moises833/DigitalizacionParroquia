<script>
  let { titulo, fase, descripcion } = $props()
</script>

<h1>{titulo}</h1>
<div class="tarjeta">
  <p>{descripcion}</p>
  <p class="suave">Esta sección se construye en la {fase} del plan.</p>
</div>
