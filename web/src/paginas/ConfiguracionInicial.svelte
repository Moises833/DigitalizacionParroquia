<script>
  import { pb, mensajeError } from "../lib/pb.js"
  import { iniciarSesion } from "../lib/sesion.svelte.js"

  let { alTerminar } = $props()

  let datos = $state({ parroquia: "", nombre: "", email: "", password: "", confirmar: "" })
  let error = $state("")
  let enviando = $state(false)

  async function enviar(evento) {
    evento.preventDefault()
    error = ""
    if (datos.password !== datos.confirmar) {
      error = "Las contraseñas no coinciden."
      return
    }
    enviando = true
    try {
      await pb.send("/api/configuracion-inicial", {
        method: "POST",
        body: { parroquia: datos.parroquia, nombre: datos.nombre, email: datos.email, password: datos.password },
      })
      await iniciarSesion(datos.email, datos.password)
      alTerminar()
    } catch (err) {
      error = mensajeError(err)
    } finally {
      enviando = false
    }
  }
</script>

<div class="pantalla">
  <form class="tarjeta" onsubmit={enviar}>
    <h1>Bienvenido</h1>
    <p class="suave">Es la primera vez que se abre el sistema. Registre la parroquia y la cuenta del administrador.</p>

    {#if error}<div class="alerta error" role="alert">{error}</div>{/if}

    <div class="campo">
      <label for="parroquia">Nombre de la parroquia</label>
      <input id="parroquia" bind:value={datos.parroquia} required />
    </div>
    <div class="campo">
      <label for="nombre">Nombre del administrador</label>
      <input id="nombre" bind:value={datos.nombre} required autocomplete="name" />
    </div>
    <div class="campo">
      <label for="email">Correo electrónico</label>
      <input id="email" type="email" bind:value={datos.email} required autocomplete="username" />
      <span class="ayuda">Se usa para iniciar sesión.</span>
    </div>
    <div class="campo">
      <label for="password">Contraseña</label>
      <input id="password" type="password" bind:value={datos.password} required minlength="8" autocomplete="new-password" />
      <span class="ayuda">Mínimo 8 caracteres.</span>
    </div>
    <div class="campo">
      <label for="confirmar">Repetir contraseña</label>
      <input id="confirmar" type="password" bind:value={datos.confirmar} required minlength="8" autocomplete="new-password" />
    </div>

    <button class="boton" type="submit" disabled={enviando}>
      {enviando ? "Guardando…" : "Comenzar"}
    </button>
  </form>
</div>

<style>
  .pantalla { min-height: 100vh; display: grid; place-items: center; padding: 1rem; }
  form { width: min(460px, 100%); }
  .boton { width: 100%; }
</style>
