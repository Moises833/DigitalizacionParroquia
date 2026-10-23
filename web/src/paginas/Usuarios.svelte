<script>
  import { onMount } from "svelte"
  import { pb, mensajeError } from "../lib/pb.js"
  import { sesion } from "../lib/sesion.svelte.js"

  const vacio = () => ({ name: "", email: "", rol: "secretaria", password: "" })

  let usuarios = $state([])
  let form = $state(vacio())
  let creando = $state(false)
  let error = $state("")
  let aviso = $state("")

  async function cargar() {
    usuarios = await pb.collection("users").getFullList({ sort: "name" })
  }

  async function crear(evento) {
    evento.preventDefault()
    error = ""
    aviso = ""
    try {
      await pb.collection("users").create({
        ...form,
        passwordConfirm: form.password,
        emailVisibility: true,
      })
      aviso = `Usuario ${form.email} creado.`
      form = vacio()
      creando = false
      await cargar()
    } catch (err) {
      error = mensajeError(err)
    }
  }

  // Contraseña nueva para alguien que olvidó la suya (solo el administrador).
  let cambiando = $state("")
  let nuevaClave = $state("")
  const enfocar = (campo) => campo.focus()

  async function cambiarClave(evento, u) {
    evento.preventDefault()
    error = ""
    aviso = ""
    try {
      await pb.collection("users").update(u.id, { password: nuevaClave, passwordConfirm: nuevaClave })
      aviso = `Contraseña de ${u.email} cambiada. Désela en persona.`
      cambiando = ""
      nuevaClave = ""
    } catch (err) {
      error = mensajeError(err)
    }
  }

  async function eliminar(u) {
    if (!confirm(`¿Eliminar al usuario ${u.email}?`)) return
    error = ""
    aviso = ""
    try {
      await pb.collection("users").delete(u.id)
      aviso = `Usuario ${u.email} eliminado.`
      await cargar()
    } catch (err) {
      error = mensajeError(err)
    }
  }

  onMount(cargar)
</script>

<div class="fila encabezado">
  <h1>Usuarios</h1>
  <span class="espaciador"></span>
  {#if !creando}<button class="boton" onclick={() => (creando = true)}>Agregar usuario</button>{/if}
</div>

{#if aviso}<div class="alerta exito" role="status">{aviso}</div>{/if}
{#if error}<div class="alerta error" role="alert">{error}</div>{/if}

{#if creando}
  <form class="tarjeta formulario" onsubmit={crear}>
    <h2>Nuevo usuario</h2>
    <div class="fila">
      <div class="campo">
        <label for="nombre">Nombre</label>
        <input id="nombre" bind:value={form.name} required />
      </div>
      <div class="campo">
        <label for="correo">Correo electrónico</label>
        <input id="correo" type="email" bind:value={form.email} required autocomplete="off" />
      </div>
    </div>
    <div class="fila">
      <div class="campo">
        <label for="rol">Rol</label>
        <select id="rol" bind:value={form.rol}>
          <option value="secretaria">Secretaría: registra y edita actas</option>
          <option value="admin">Administrador: además gestiona usuarios y borra registros</option>
        </select>
      </div>
      <div class="campo">
        <label for="clave">Contraseña inicial</label>
        <input id="clave" type="password" bind:value={form.password} required minlength="8" autocomplete="new-password" />
      </div>
    </div>
    <div class="fila">
      <button class="boton" type="submit">Crear usuario</button>
      <button class="boton secundario" type="button" onclick={() => (creando = false)}>Cancelar</button>
    </div>
  </form>
{/if}

<div class="tabla-contenedor">
  <table class="tabla">
    <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th></th></tr></thead>
    <tbody>
      {#each usuarios as u (u.id)}
        <tr>
          <td>{u.name}</td>
          <td>{u.email}</td>
          <td>{u.rol === "admin" ? "Administrador" : "Secretaría"}</td>
          <td class="acciones">
            {#if u.id !== sesion.usuario.id}
              <button class="boton secundario" onclick={() => { cambiando = cambiando === u.id ? "" : u.id; nuevaClave = "" }}>
                Cambiar contraseña
              </button>
              <button class="boton peligro" onclick={() => eliminar(u)}>Eliminar</button>
            {/if}
          </td>
        </tr>
        {#if cambiando === u.id}
          <tr>
            <td colspan="4">
              <form class="fila clave" onsubmit={(e) => cambiarClave(e, u)}>
                <div class="campo">
                  <label for="nueva-{u.id}">Contraseña nueva para {u.name || u.email}</label>
                  <input id="nueva-{u.id}" type="password" bind:value={nuevaClave} required minlength="8" autocomplete="new-password" use:enfocar />
                </div>
                <button class="boton" type="submit">Guardar</button>
                <button class="boton secundario" type="button" onclick={() => (cambiando = "")}>Cancelar</button>
              </form>
            </td>
          </tr>
        {/if}
      {/each}
    </tbody>
  </table>
</div>

<style>
  .encabezado { margin-bottom: 1rem; }
  .encabezado h1 { margin: 0; }
  .formulario { margin-bottom: 1.5rem; }
  .acciones { text-align: right; }
  .acciones { white-space: nowrap; }
  .acciones .boton { min-height: 36px; padding: 0.3rem 0.8rem; margin-left: 0.4rem; }
  .clave { align-items: flex-end; }
  .clave .campo { margin-bottom: 0; max-width: 340px; }
</style>
