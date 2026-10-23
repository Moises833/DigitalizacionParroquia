<script>
  import { onMount } from "svelte"
  import { pb } from "./lib/pb.js"
  import { sesion, esAdmin, cerrarSesion, renovarSesion } from "./lib/sesion.svelte.js"
  import { ruta } from "./lib/ruta.svelte.js"
  import ConfiguracionInicial from "./paginas/ConfiguracionInicial.svelte"
  import Login from "./paginas/Login.svelte"
  import Inicio from "./paginas/Inicio.svelte"
  import Libros from "./paginas/Libros.svelte"
  import Usuarios from "./paginas/Usuarios.svelte"
  import Parroquia from "./paginas/Parroquia.svelte"
  import Captura from "./paginas/Captura.svelte"
  import Actas from "./paginas/Actas.svelte"
  import EditorPagina from "./paginas/EditorPagina.svelte"
  import Movil from "./paginas/Movil.svelte"

  let estado = $state("cargando") // cargando | configurar | listo | sin-conexion

  const menu = [
    { id: "inicio", texto: "Inicio" },
    { id: "actas", texto: "Actas" },
    { id: "captura", texto: "Captura de páginas" },
    { id: "libros", texto: "Libros" },
    { id: "usuarios", texto: "Usuarios", soloAdmin: true },
    { id: "parroquia", texto: "Parroquia", soloAdmin: true },
  ]

  // La pantalla del teléfono (#/movil/<token>) no usa sesión de usuario.
  let tokenMovil = $derived(ruta.actual.startsWith("movil/") ? ruta.actual.slice("movil/".length) : "")
  let paginaId = $derived(ruta.actual.startsWith("pagina/") ? ruta.actual.slice("pagina/".length) : "")
  let seccion = $derived(paginaId ? "actas" : ruta.actual)

  async function comprobar() {
    estado = "cargando"
    try {
      const r = await pb.send("/api/configuracion-inicial", { method: "GET" })
      if (r.pendiente) {
        pb.authStore.clear()
        estado = "configurar"
        return
      }
      await renovarSesion()
      estado = "listo"
    } catch {
      estado = "sin-conexion"
    }
  }

  onMount(() => {
    if (!tokenMovil) comprobar()
  })
</script>

{#if tokenMovil}
  <Movil token={tokenMovil} />
{:else if estado === "cargando"}
  <p class="centro suave">Cargando…</p>
{:else if estado === "sin-conexion"}
  <div class="centro">
    <div class="tarjeta">
      <h2>No hay conexión con el servidor</h2>
      <p class="suave">Revise que el sistema esté iniciado en la computadora principal.</p>
      <button class="boton" onclick={comprobar}>Reintentar</button>
    </div>
  </div>
{:else if estado === "configurar"}
  <ConfiguracionInicial alTerminar={comprobar} />
{:else if !sesion.usuario}
  <Login />
{:else}
  <div class="marco">
    <nav class="lateral" aria-label="Menú principal">
      <div class="marca">Libro de Bautismos</div>
      <ul>
        {#each menu.filter((m) => !m.soloAdmin || esAdmin()) as item}
          <li>
            <a href="#/{item.id}" class:activo={seccion === item.id}
               aria-current={seccion === item.id ? "page" : undefined}>{item.texto}</a>
          </li>
        {/each}
      </ul>
      <div class="usuario">
        <div>{sesion.usuario.name || sesion.usuario.email}</div>
        <div class="suave rol">{sesion.usuario.rol === "admin" ? "Administrador" : "Secretaría"}</div>
        <button class="boton secundario" onclick={cerrarSesion}>Cerrar sesión</button>
      </div>
    </nav>

    <main class="contenido" class:ancho={paginaId}>
      {#if paginaId}
        {#key paginaId}<EditorPagina id={paginaId} />{/key}
      {:else if ruta.actual === "inicio"}
        <Inicio />
      {:else if ruta.actual === "actas"}
        <Actas />
      {:else if ruta.actual === "libros"}
        <Libros />
      {:else if ruta.actual === "captura"}
        <Captura />
      {:else if ruta.actual === "usuarios" && esAdmin()}
        <Usuarios />
      {:else if ruta.actual === "parroquia" && esAdmin()}
        <Parroquia />
      {:else}
        <h1>Página no encontrada</h1>
        <a href="#/inicio">Volver al inicio</a>
      {/if}
    </main>
  </div>
{/if}

<style>
  .centro { min-height: 100vh; display: grid; place-items: center; padding: 1rem; }
  .marco { display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; }
  .lateral {
    background: var(--primario); color: #fff; padding: 1.25rem 1rem;
    display: flex; flex-direction: column; gap: 1rem;
  }
  .marca { font-family: var(--fuente-titulo); font-size: 1.25rem; font-weight: 600; }
  ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.25rem; }
  ul a {
    display: block; padding: 0.55rem 0.75rem; border-radius: var(--radio);
    color: #fff; text-decoration: none; opacity: 0.85;
  }
  ul a:hover { background: rgb(255 255 255 / 0.1); opacity: 1; }
  ul a.activo { background: rgb(255 255 255 / 0.18); opacity: 1; font-weight: 600; }
  .usuario { margin-top: auto; font-size: 0.95rem; display: flex; flex-direction: column; gap: 0.4rem; }
  .usuario .rol { color: rgb(255 255 255 / 0.75); font-size: 0.85rem; }
  .contenido { padding: 2rem; max-width: 1100px; width: 100%; min-width: 0; }
  .contenido.ancho { max-width: 1500px; }

  @media (max-width: 760px) {
    .marco { grid-template-columns: 1fr; }
    .lateral { flex-direction: row; flex-wrap: wrap; align-items: center; padding: 0.75rem 1rem; }
    ul { flex-direction: row; flex-wrap: wrap; }
    .usuario { margin-top: 0; flex-direction: row; align-items: center; width: 100%; }
    .contenido { padding: 1rem; }
  }
</style>
