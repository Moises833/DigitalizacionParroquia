import { defineConfig } from "vite"
import { svelte } from "@sveltejs/vite-plugin-svelte"

// En desarrollo, Vite reenvía las llamadas a PocketBase (puerto 8090).
// En producción, PocketBase sirve directamente el resultado de `npm run build`.
export default defineConfig({
  plugins: [svelte()],
  build: {
    outDir: "../servidor/pb_public",
    emptyOutDir: true,
  },
  server: {
    proxy: {
      "/api": "http://127.0.0.1:8090",
      "/_": "http://127.0.0.1:8090",
    },
  },
})
