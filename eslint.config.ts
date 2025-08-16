import js from '@eslint/js';
import globals from 'globals';
import tseslint from 'typescript-eslint';
import json from '@eslint/json';
import markdown from '@eslint/markdown';
import css from '@eslint/css';
import { defineConfig } from 'eslint/config';
import { fileURLToPath } from 'node:url';
import { includeIgnoreFile } from '@eslint/compat';

const gitignorePath = fileURLToPath(new URL('.gitignore', import.meta.url));

export default defineConfig([
  includeIgnoreFile(gitignorePath, 'Imported .gitignore patterns'),
  {
    files: [ '**/*.{js,mjs,cjs,ts,mts,cts}' ],
    plugins: { js },
    extends: [ 'js/recommended' ],
    rules: {
      quotes: [ 'error', 'single' ],
    },
    languageOptions: { globals: { ...globals.browser, ...globals.node } }
  },
  tseslint.configs.recommended,
  { files: [ '**/*.json' ], plugins: { json }, language: 'json/json', extends: [ 'json/recommended' ] },
  {
    files: [ '**/*.md' ], plugins: { markdown }, language: 'markdown/gfm', extends: [ 'markdown/recommended' ], rules: {
      'markdown/no-missing-label-refs': 'off'
    }
  },
  {
    files: [ '**/*.css' ],
    plugins: { css },
    language: 'css/css',
    extends: [ 'css/recommended' ],
    rules: {
      'css/no-invalid-at-rules': 'off',
      'css/no-important': 'off',
      'css/use-baseline': 'off',
      'css/no-invalid-properties': 'off'
    }
  },
]);
