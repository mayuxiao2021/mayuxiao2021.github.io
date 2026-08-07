# 博客维护文档

> 最后更新：2026-08-07 | Astro 6.4.8 | Svelte 5.56.8 | Node ≥22.12 | pnpm 9

---

## 1. 项目概览

基于 [Fuwari](https://github.com/saicaca/fuwari) 主题的 Astro 静态博客，部署在 GitHub Pages，自定义域名 `github.glmyx.eu.org`，Cloudflare CDN 代理。

### 技术栈

| 技术 | 版本 | 用途 |
|------|------|------|
| Astro | 6.4.8 | 静态站点生成器 |
| Svelte | 5.56.8 | 交互组件 (Search, LightDarkSwitch, DisplaySettings) |
| Tailwind CSS | 3.4.7 | 原子化 CSS 框架 |
| Pagefind | 1.5.2 | 静态搜索 |
| KaTeX | 0.18.1 | 数学公式渲染 |
| Mermaid | 11.16.1 | 图表渲染 (按需加载) |
| PhotoSwipe | 5.4.4 | 图片灯箱 (按需加载) |
| pnpm | 9.6.0 | 包管理器 |

---

## 2. 项目结构

```
.
├── astro.config.mjs          # Astro 核心配置
├── package.json              # 依赖 & 构建脚本
├── tailwind.config.cjs       # Tailwind 配置 (class 暗色模式)
├── tsconfig.json             # TypeScript 路径别名
├── biome.json                # 代码格式化/Lint
├── .env                      # BASE_URL=/ (防止环境泄漏)
├── .github/workflows/main.yml # CI/CD 部署流水线
│
├── public/                   # 静态资源 (直接复制到 dist)
│   ├── banners/              # 首页随机背景图
│   ├── favicon/              # 网站图标
│   ├── gallery/              # 作品集
│   └── ...                   # 其他游戏/演示项目
│
└── src/
    ├── config.ts             # 站点配置 (标题/导航/社交链接)
    ├── content.config.ts     # Content Layer 集合定义 (posts, spec)
    │
    ├── content/
    │   ├── posts/            # 博客文章 (.md)
    │   └── spec/about.md     # "关于"页面
    │
    ├── pages/                # 路由页面
    │   ├── index.astro → [...page].astro    # 首页 (分页)
    │   ├── about.astro                       # 关于页
    │   ├── posts/[...slug].astro             # 文章详情
    │   ├── archive/                          # 归档 (分类/标签)
    │   ├── robots.txt.ts                     # SEO robots
    │   └── rss.xml.ts                        # RSS 订阅
    │
    ├── layouts/
    │   ├── Layout.astro       # 根布局 (<html>/<head>/SEO)
    │   └── MainGridLayout.astro # 主网格布局 (导航+侧栏+内容)
    │
    ├── components/
    │   ├── GlobalStyles.astro # 全局 CSS 变量 & Tailwind @layer
    │   ├── Navbar.astro       # 顶部导航
    │   ├── Footer.astro       # 页脚
    │   ├── PostCard.astro     # 文章卡片
    │   ├── PostPage.astro     # 文章列表页
    │   ├── Search.svelte      # 搜索组件 (Svelte 5)
    │   ├── LightDarkSwitch.svelte # 亮暗切换 (Svelte 5)
    │   ├── misc/
    │   │   ├── Markdown.astro # Markdown 渲染容器
    │   │   └── ImageWrapper.astro # 响应式图片
    │   └── widget/            # 侧栏组件
    │
    ├── i18n/                  # 国际化 (zh_CN/en/ja/ko/zh_TW)
    ├── constants/             # 常量 (分页大小/主题模式)
    ├── plugins/               # Remark/Rehype 插件 (admonition/github-card)
    ├── types/                 # TypeScript 类型定义
    └── utils/                 # 工具函数
```

---

## 3. 本地开发

### 环境要求

- **Node.js** ≥ 22.12.0
- **pnpm** 9.x (`npm i -g pnpm@9`)

### 常用命令

```bash
pnpm install          # 安装依赖
pnpm dev              # 开发服务器 (localhost:4321)
pnpm build            # 生产构建 → dist/
pnpm preview          # 预览构建产物
pnpm new-post         # 创建新文章
pnpm format           # 格式化代码 (Biome)
pnpm lint             # 代码检查 (Biome)
```

### 环境变量警告

本机的 `BASE_URL` 环境变量可能被 DeepCode 工具污染为 `https://api.deepseek.com`。`.env` 文件已修复此问题，CI 环境不受影响。

---

## 4. 内容管理

### 创建新文章

```bash
pnpm new-post
```

或在 `src/content/posts/` 下创建 `.md` 文件：

```markdown
---
title: 文章标题
published: 2026-01-01
description: 文章摘要
image: ./cover.jpg       # 可选：封面图
tags: [标签1, 标签2]
category: 分类名
draft: false              # true = 草稿（不会出现在生产环境）
---

文章内容...
```

### 文章目录结构

```
src/content/posts/
├── draft.md              # 草稿示例
├── markdown.md           # 单文件文章
├── markdown-extended.md
├── video.md
└── guide/
    ├── index.md          # 带图片的文章 (推荐方式)
    └── cover.jpeg
```

### "关于"页面

编辑 `src/content/spec/about.md`

---

## 5. 部署

### 部署流程

```
git push master → GitHub Actions 触发
  ├── pnpm/action-setup@v4 (自动读取 packageManager)
  ├── actions/setup-node@v4 (Node 22)
  ├── pnpm install --frozen-lockfile
  ├── pnpm build (astro build + pagefind)
  ├── upload-pages-artifact@v3 (上传 dist/)
  └── deploy-pages@v4 (部署到 GitHub Pages)
```

### 部署文件

- `.github/workflows/main.yml` — CI 配置
- `CNAME` — 自定义域名 `github.glmyx.eu.org`

### 部署排障

| 症状 | 原因 | 解决 |
|------|------|------|
| 部署不更新 | GH Pages Source 非 Actions | Settings→Pages→Source 选 "GitHub Actions" |
| pnpm version 冲突 | workflow 和 package.json 重复指定版本 | workflow 不要写 `version:`，自动检测 |
| Node 版本错误 | Astro 6 要求 ≥22.12 | workflow 中 `node-version: 22` |
| 链接损坏 | 本地 BASE_URL 环境泄漏 | 已通过 `.env` + `url-utils.ts` 双重防护 |

---

## 6. 核心配置说明

### astro.config.mjs

```js
export default defineConfig({
  site: "https://github.glmyx.eu.org",
  base: "/",
  trailingSlash: "always",        // 所有 URL 以 / 结尾
  integrations: [
    tailwind(),                    // Tailwind CSS
    swup({ ... }),                 // 页面切换动画
    icon({ ... }),                 // SVG 图标
    svelte(),                      // Svelte 组件
    sitemap({ changefreq, priority }),
    Compress({ CSS: true, Image: true }), // 构建压缩
  ],
})
```

### src/config.ts — 站点配置

```ts
siteConfig: { title, subtitle, lang, themeColor, banner, favicon }
navBarConfig: { links: [Home, Archive, About, Gallery, GitHub] }
profileConfig: { avatar, name, bio, links }
licenseConfig: { enable, name, url }
FRIENDLY_LINKS: [...]
```

### src/content.config.ts — Content Layer

```ts
// 博客文章集合
postsCollection = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/posts' }),
  schema: z.object({ title, published, draft, description, image, tags, category })
})
// "关于"页面集合
specCollection = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/spec' }),
})
```

---

## 7. 关键改动记录 (2026-08)

本次优化从 Astro 4.12 → 6.4.8，以下记录所有关键改动，方便后续维护：

### 架构变更

| 改动 | 旧方式 | 新方式 |
|------|--------|--------|
| Content API | `src/content/config.ts` + `entry.render()` | `src/content.config.ts` + `render(entry)` |
| Entry 标识 | `entry.slug` | `entry.id` (需 `.replace(/\/index$/, '')`) |
| CSS 预处理 | stylus + scss 混用 | **全部转为纯 CSS + Tailwind @layer** |
| Svelte | v4 (`$:`, `on:click`, `export let`) | v5 (`$state`, `$effect`, `onclick`) |
| 图片列表 | `node:fs.readdirSync` | `import.meta.glob` |
| 图片导入 | `import.meta.glob(..., { import: 'default' })` 返回即值 | 返回 `() => promise`，需 `await mod()` |

### 样式架构

**所有 stylus/scss 已转为纯 CSS**。原因：Astro 6 的 lightningcss 不兼容 stylus `&` 嵌套。

- `src/components/GlobalStyles.astro` — 全局 CSS 变量 + Tailwind `@layer components`
- `src/layouts/Layout.astro` — banner 动画 + reduced-motion
- `src/components/misc/Markdown.astro` — 文章样式、代码块、admonition

### URL 工具防护

`src/utils/url-utils.ts` 的 `url()` 函数已加入清洗逻辑：当 `BASE_URL` 被环境污染为非法值（如 `https://api.deepseek.com`）时自动回退到 `/`。

### SEO 增强

- `<link rel="canonical">` — 页面规范 URL
- `og:type` / `og:image` — Open Graph 完善
- `<script type="application/ld+json">` — 首页 WebSite + 文章 BlogPosting 结构化数据

---

## 8. 常见问题

### Q: 本地 `pnpm build` 失败 "lightningcss Unexpected token Delim('&')"

项目中已无 stylus/scss。如有残留，用 `grep -rn 'lang="stylus"\|lang="scss"' src/` 排查。

### Q: 构建产物中 `dist/posts/` 只有 index.html 没有文章页

`getStaticPaths` 中 `entry.slug` 应改为 `entry.id.replace(/\/index$/, '')`。Content Layer API 使用 `id` 而非 `slug`。

### Q: 本地链接显示 `/D:/Git/...` 或 `/https:/api.deepseek.com/...`

环境变量 `BASE_URL` 被污染。确保 `.env` 文件存在且内容为 `BASE_URL=/`，并重启 dev server。

### Q: Svelte 组件报错 "on:click is deprecated"

Svelte 5 中事件语法变更：`on:click` → `onclick`，`$:` → `$effect()`，`let x = 0` → `let x = $state(0)`。

### Q: KaTeX 公式不显示

KaTeX CSS 已从 CDN 改为本地打包 (`import 'katex/dist/katex.min.css'`)。确认 `katex` 在 `dependencies` 中。

---

## 9. 性能特征

| 指标 | 值 |
|------|-----|
| 构建产物 (dist/_astro) | ~9MB |
| 最大 JS chunk | Mermaid (~1.3MB, 按需加载) |
| 首页 JS | ~50KB (含 Swup + Search + 主题切换) |
| PhotoSwipe | 独立 chunk, 无图片页面 0KB |
| KaTeX | CDN 0 引用, 全部本地 |
| 字体 | Roboto + JetBrains Mono, woff2 格式 |

---

## 10. 检查清单

日常维护时快速检查：

- [ ] `pnpm build` 无报错
- [ ] `dist/index.html` 包含 `Astro v6.4.8`
- [ ] `dist/posts/` 下有文章页面 (非空)
- [ ] `grep -r 'cdn.staticfile.net' dist/` 无结果
- [ ] `grep -r 'lang="stylus"' src/` 无结果
- [ ] `.github/workflows/main.yml` 中 `node-version: 22`
- [ ] `.env` 文件存在且 `BASE_URL=/`
- [ ] GitHub Actions 最近运行成功
