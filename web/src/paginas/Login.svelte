<script>
  import { mensajeError } from "../lib/pb.js"
  import { iniciarSesion } from "../lib/sesion.svelte.js"

  let email = $state("")
  let password = $state("")
  let error = $state("")
  let enviando = $state(false)

  async function enviar(evento) {
    evento.preventDefault()
    error = ""
    enviando = true
    try {
      await iniciarSesion(email, password)
    } catch (err) {
      error = err?.status === 400 ? "Correo o contraseña incorrectos." : mensajeError(err)
    } finally {
      enviando = false
    }
  }
</script>

<div class="pantalla">
  <form class="tarjeta" onsubmit={enviar}>
    <h1>Libro de Bautismos</h1>
    <p class="suave">Inicie sesión para continuar.</p>

    {#if error}<div class="alerta error" role="alert">{error}</div>{/if}

    <div class="campo">
      <label for="email">Correo electrónico</label>
      <input id="email" type="email" bind:value={email} required autocomplete="username" />
    </div>
    <div class="campo">
      <label for="password">Contraseña</label>
      <input id="password" type="password" bind:value={password} required autocomplete="current-password" />
    </div>

    <button class="boton" type="submit" disabled={enviando}>
      {enviando ? "Entrando…" : "Entrar"}
    </button>
  </form>
</div>

<style>
  .pantalla { min-height: 100vh; display: grid; place-items: center; padding: 1rem; }
  form { width: min(400px, 100%); }
  .boton { width: 100%; }
</style>
