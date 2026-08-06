import sitemap from '@astrojs/sitemap';
import svelte from "@astrojs/svelte"
import tailwind from "@astrojs/tailwind"
import swup from '@swup/astro';
import Compress from "astro-compress"
import icon from "astro-icon"
import { defineConfig } from "astro/config"

// https://astro.build/config
export default defineConfig({
  site: "https://github.glmyx.eu.org",
  base: "/",
  trailingSlash: "always",
  integrations: [
    tailwind(),
    swup({
      theme: false,
      animationClass: 'transition-swup-',
      containers: ['main'],
      smoothScrolling: true,
      cache: true,
      preload: true,
      accessibility: true,
      updateHead: true,
      updateBodyClass: false,
      globalInstance: true,
    }),
    icon({
      include: {
        "material-symbols": ["*"],
        "fa6-brands": ["*"],
        "fa6-regular": ["*"],
        "fa6-solid": ["*"],
      },
    }),
    svelte(),
    sitemap({
      changefreq: 'weekly',
      priority: 0.7,
    }),
    Compress({
      CSS: true,
      Image: true,
      Action: {
        Passed: async () => true,
      },
    }),
  ],
  vite: {
    build: {
      rollupOptions: {
        onwarn(warning, warn) {
          if (warning.message.includes("is dynamically imported by") && warning.message.includes("but also statically imported by")) {
            return;
          }
          warn(warning);
        }
      }
    },
  },
})
