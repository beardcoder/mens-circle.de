import type { Config } from "tailwindcss";
import defaultTheme from "tailwindcss/defaultTheme";
// @ts-ignore
import typography from "flowbite-typography";
import flowbite from "flowbite/plugin";
// @ts-ignore
import forms from "@tailwindcss/forms";

export default {
  content: [
    "./packages/sitepackage/**/*.html",
    "./packages/sitepackage/**/*.ts",
    "./packages/sitepackage/**/*.js",
    "./packages/sitepackage/**/*.yaml",
    "./node_modules/flowbite/**/*.js",
  ],
  darkMode: "media",
  theme: {
    extend: {
      typography: {
        DEFAULT: {
          css: {
            color: "#0c0a09",
            a: {
              color: "#B76F2B",
              "&:hover": {
                color: "#5C3816",
              },
            },
          },
        },
        invert: {
          css: {
            color: "#fafaf9",
            a: {
              color: "#B76F2B",
              "&:hover": {
                color: "#5C3816",
              },
            },
          },
        },
      },
      fontFamily: {
        sans: ['"Jost Variable"', ...defaultTheme.fontFamily.sans],
        special: ['"Nothing You Could Do"', ...defaultTheme.fontFamily.serif],
      },
      fontSize: {
        fluid: "clamp(2.5rem, -0.5718rem + 11.7021vw, 9.375rem)",
      },
      colors: {
        primary: {
          DEFAULT: "#B76F2B",
          50: "#ECCCAE",
          100: "#E8C19D",
          200: "#E0AD7C",
          300: "#D8985B",
          400: "#D1833A",
          500: "#B76F2B",
          600: "#8A5320",
          700: "#5C3816",
          800: "#2F1C0B",
          900: "#010100",
          950: "#000000",
        },
        secondary: {
          DEFAULT: "#004C45",
          50: "#F2FFFE",
          100: "#D6FFFB",
          200: "#9EFFF6",
          300: "#66FFF1",
          400: "#2DFFEC",
          500: "#00F4DE",
          600: "#00BCAB",
          700: "#008478",
          800: "#004C45",
          900: "#002320",
          950: "#000F0D",
        },
      },
    },
  },
  plugins: [typography, flowbite, forms],
} satisfies Config;
