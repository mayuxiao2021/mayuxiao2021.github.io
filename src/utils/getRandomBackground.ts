import fs from 'node:fs'
import path from 'node:path'
import { siteConfig } from '../config'
export function getRandomBackground(): string {
  const backgroundsDir = path.join(
    process.cwd(),
    `/public/${siteConfig.banner.src}`,
  )
  const files = fs.readdirSync(backgroundsDir).filter(f => /\.(png|jpg|jpeg|webp|avif)$/i.test(f))
  const randomFile = files[Math.floor(Math.random() * files.length)]
  return `/${siteConfig.banner.src}/${randomFile}`
}
export function getBackground(): string {
  const backgroundsDir = path.join(
    process.cwd(),
    `/public/${siteConfig.banner.src}`,
  )
  const files = fs.readdirSync(backgroundsDir).filter(f => /\.(png|jpg|jpeg|webp|avif)$/i.test(f))
  const FileTree = files[Math.floor(Math.random() * files.length)]
  return `/${siteConfig.banner.src}/${FileTree}`
}
