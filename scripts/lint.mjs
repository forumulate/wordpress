#!/usr/bin/env node
// php -l every PHP file in the plugin. Exits non-zero on the first syntax error.
import { execFileSync } from 'node:child_process';
import { readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';

const root = new URL('..', import.meta.url).pathname;
const skip = new Set(['.git', 'node_modules', 'vendor']);
const files = [];
(function walk(dir) {
  for (const name of readdirSync(dir)) {
    if (skip.has(name)) continue;
    const abs = join(dir, name);
    if (statSync(abs).isDirectory()) walk(abs);
    else if (name.endsWith('.php')) files.push(abs);
  }
})(root);
for (const f of files) execFileSync('php', ['-l', f], { stdio: ['ignore', 'ignore', 'inherit'] });
console.log(`${files.length} PHP files OK`);
