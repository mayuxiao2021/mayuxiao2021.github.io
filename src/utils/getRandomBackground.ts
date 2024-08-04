import fs from 'node:fs'
import path from 'node:path'
import { siteConfig } from '../config'
export function getRandomBackground(): string {
  const backgroundsDir = path.join(process.cwd(), siteConfig.banner.src)
  const files = fs.readdirSync(backgroundsDir)
  const randomFile = files[Math.floor(Math.random() * files.length)]
  return `/${siteConfig.banner.src}/${randomFile}`
}