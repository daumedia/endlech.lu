import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';

export default tseslint.config(
    eslint.configs.recommended,
    ...tseslint.configs.recommended,
    {
        ignores: ['public/assets/', 'node_modules/', 'vendor/', 'assets/vendor/', 'var/'],
    },
    {
        // Der Polyfill-Lader ist ein klassisches Skript, kein Modul (er muss in Browsern
        // ohne Importmap laufen) — deshalb `script` und die beiden Browser-Globalen von Hand.
        files: ['assets/es_module_shims_loader.js'],
        languageOptions: {
            sourceType: 'script',
            globals: { document: 'readonly', HTMLScriptElement: 'readonly' },
        },
    },
);
