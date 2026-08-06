export function getRandomBackground(): string {
  // In SSR/SSG, we receive the banner list from the Layout
  // Default fallback
  return '/banners/demo-banner.webp'
}
