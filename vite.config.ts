import browserslist from 'browserslist'
import { browserslistToTargets } from 'lightningcss'
import { defineConfig } from 'vite'
import typo3 from 'vite-plugin-typo3'

export default defineConfig({
    build: {
        cssMinify: 'lightningcss',
    },
    css: {
        lightningcss: {
            targets: browserslistToTargets(browserslist('>= 1%')),
        },
        transformer: 'lightningcss',
    },
    plugins: [typo3()],
})
