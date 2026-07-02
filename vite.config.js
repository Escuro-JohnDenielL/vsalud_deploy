import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import fs from 'node:fs';
import path from 'node:path';

function getFilesRecursive(dir, ext) {
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    const files = [];
    for (const entry of entries) {
        const fullPath = path.join(dir, entry.name);
        if (entry.isDirectory()) {
            files.push(...getFilesRecursive(fullPath, ext));
        } else if (entry.name.endsWith(ext)) {
            files.push(fullPath);
        }
    }
    return files;
}

const cssInputs = getFilesRecursive(path.resolve('resources/css'), '.css');
const jsInputs = getFilesRecursive(path.resolve('resources/js'), '.js');

export default defineConfig({
    plugins: [
        laravel({
            input: [...cssInputs, ...jsInputs],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        cors: {
            origin: ['http://localhost:8000', 'https://z825b5tt-8000.asse.devtunnels.ms'],
            credentials: true,
        },
        hmr: {
            host: 'localhost',
            protocol: 'ws',
        },
    },
});
