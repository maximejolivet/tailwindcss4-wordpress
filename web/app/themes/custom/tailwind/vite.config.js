import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '../../../../..');
const host = 'tailwind-wordpress.localhost';
const port = 3009;

const certPath = path.join(repoRoot, 'docker/traefik/certs', `${host}.pem`);
const keyPath = path.join(repoRoot, 'docker/traefik/certs', `${host}-key.pem`);
const https =
    fs.existsSync(certPath) && fs.existsSync(keyPath)
        ? { cert: fs.readFileSync(certPath), key: fs.readFileSync(keyPath) }
        : true;

// Twig/PHP changes don't go through Vite's module graph, so trigger a full
// browser reload for them ourselves.
function phpTwigReload() {
    return {
        name: 'php-twig-reload',
        configureServer(server) {
            server.watcher.add([
                path.join(__dirname, '**/*.twig'),
                path.join(__dirname, '**/*.php'),
            ]);
            server.watcher.on('change', (file) => {
                if (/\.(twig|php)$/.test(file)) {
                    server.ws.send({ type: 'full-reload' });
                }
            });
        },
    };
}

export default defineConfig(({ command }) => ({
    base: command === 'build' ? '/app/themes/custom/tailwind/dist/' : '/',
    plugins: [tailwindcss(), phpTwigReload()],
    build: {
        manifest: true,
        outDir: 'dist',
        emptyOutDir: true,
        rollupOptions: {
            input: 'assets/scripts/app.js',
        },
    },
    server: {
        host: '0.0.0.0',
        port,
        strictPort: true,
        https,
        origin: `https://${host}:${port}`,
        cors: true,
        hmr: {
            host,
            protocol: 'wss',
        },
        // Bind-mounted volumes (Colima/virtiofs) don't reliably propagate
        // inotify events from the macOS host, so fall back to polling.
        watch: {
            usePolling: true,
            interval: 300,
        },
    },
}));
