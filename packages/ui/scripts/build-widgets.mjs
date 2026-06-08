import * as esbuild from 'esbuild';
import { mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const outDir = join(root, 'resources/js');

mkdirSync(outDir, { recursive: true });

const shared = {
    bundle: true,
    format: 'iife',
    platform: 'browser',
    target: ['es2020'],
    logLevel: 'info',
};

const entries = [
    { in: 'resources/js/src/pv-rich-text.entry.js', out: 'pv-rich-text.js' },
    { in: 'resources/js/src/pv-code-editor.entry.js', out: 'pv-code-editor.js' },
    { in: 'resources/js/src/pv-code-display.entry.js', out: 'pv-code-display.js' },
    { in: 'resources/js/src/pv-graph.entry.js', out: 'pv-graph.js' },
    { in: 'resources/js/src/pv-pivot.entry.js', out: 'pv-pivot.js' },
];

for (const { in: entry, out } of entries) {
    await esbuild.build({
        ...shared,
        entryPoints: [join(root, entry)],
        outfile: join(outDir, out),
    });
}
