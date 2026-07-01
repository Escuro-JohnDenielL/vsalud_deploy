import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import fs from 'node:fs';
import path from 'node:path';

const cssInputs = fs
    .readdirSync(path.resolve('resources/css'))
    .filter((file) => file.endsWith('.css'))
    .map((file) => `resources/css/${file}`);

const jsInputs = fs
    .readdirSync(path.resolve('resources/js'))
    .filter((file) => file.endsWith('.js'))
    .map((file) => `resources/js/${file}`);

export default defineConfig({
    plugins: [
        laravel({
            input: [...cssInputs, ...jsInputs],
            refresh: true,
        }),
    ],
});
