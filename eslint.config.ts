import js from '@eslint/js'
import globals from 'globals'
import tseslint from 'typescript-eslint'
import json from '@eslint/json'
import css from '@eslint/css'
import { defineConfig } from 'eslint/config'
import { fileURLToPath } from 'node:url'
import { includeIgnoreFile } from '@eslint/compat'
import eslintPluginPrettierRecommended from 'eslint-plugin-prettier/recommended'

const gitignorePath = fileURLToPath(new URL('.gitignore', import.meta.url))

export default defineConfig([
    includeIgnoreFile(gitignorePath, 'Imported .gitignore patterns'),
    tseslint.configs.recommended,
    {
        files: ['**/*.{js,mjs,cjs,ts,mts,cts}'],
        plugins: { js },
        extends: ['js/recommended'],
        rules: {
            quotes: ['error', 'single'],
            '@typescript-eslint/no-explicit-any': 'off',
            'no-unused-vars': 'off',
        },
        languageOptions: { globals: { ...globals.browser, ...globals.node } },
    },
    { files: ['**/*.json'], plugins: { json }, language: 'json/json', extends: ['json/recommended'] },
    {
        files: ['**/*.css'],
        plugins: { css },
        language: 'css/css',
        extends: ['css/recommended'],
        rules: {
            'css/no-invalid-at-rules': 'off',
            'css/no-important': 'off',
            'css/use-baseline': 'off',
            'css/font-family-fallbacks': 'off',
            'css/no-invalid-properties': 'off',
        },
    },
    eslintPluginPrettierRecommended,
])
