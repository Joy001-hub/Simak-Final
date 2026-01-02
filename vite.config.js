import { defineConfig } from 'vite';
import angular from '@analogjs/vite-plugin-angular';
import { resolve, join } from 'path';
import { copyFileSync, existsSync } from 'fs';

const stripSourceMapComment = () => ({
    name: 'strip-sourcemap-comment',
    enforce: 'pre',
    transform(code, id) {
        if (!id.match(/\.(ts|js)$/)) return null;
        const cleaned = code.replace(/\/\/# sourceMappingURL=.*$/gm, '');
        return cleaned === code ? null : { code: cleaned, map: null };
    },
});

export default defineConfig({
    base: './',
    plugins: [
        angular(),
        stripSourceMapComment(),
        {
            name: 'copy-vite-manifest',
            closeBundle() {
                const src = join(process.cwd(), 'public', 'build', '.vite', 'manifest.json');
                const dest = join(process.cwd(), 'public', 'build', 'manifest.json');
                if (existsSync(src)) {
                    copyFileSync(src, dest);
                }
            },
        },
    ],
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: true,
        sourcemap: false,
        chunkSizeWarningLimit: 1200,
        minify: 'terser',
        rollupOptions: {
            input: {
                main: resolve(__dirname, 'index.html'),
                app: resolve(__dirname, 'resources/js/app.js'),
                style: resolve(__dirname, 'resources/css/app.css'),
            },
        },
    },
    esbuild: {
        sourcemap: false,
    },
    optimizeDeps: {
        esbuildOptions: {
            sourcemap: false,
        },
    },
    publicDir: false,
    resolve: {
        alias: {
            '@': resolve(__dirname, 'src'),
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
    },
});
