import { defineConfig } from 'vite'
import typo3 from 'vite-plugin-typo3'
import { browserslistToTargets } from 'lightningcss'
import browserslist from 'browserslist'

export default defineConfig({
    plugins: [typo3()],
    css: {
        transformer: 'lightningcss',
        lightningcss: {
            targets: browserslistToTargets(browserslist('>= 1%')),
        },
    },
    build: {
        cssMinify: 'lightningcss',
    },
})
