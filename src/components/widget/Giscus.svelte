<script>
  import { onMount } from 'svelte';

  // ===== 在这里填入你的 Giscus 配置 =====
  // 前往 https://giscus.app/ 获取以下参数
  const GISCUS_CONFIG = {
    repo: 'mayuxiao2021/mayuxiao2021.github.io',   // 格式: 用户名/仓库名
    repoId: 'R_kgDOMV2rRA',
    category: 'Announcements',
    categoryId: 'DIC_kwDOMV2rRM4DCuID',
    mapping: 'pathname',
    strict: '0',
    reactionsEnabled: '1',
    emitMetadata: '0',
    inputPosition: 'top',
    lang: 'zh-CN',
  };

  let theme = 'preferred_color_scheme';

  function updateTheme() {
    const isDark = document.documentElement.classList.contains('dark');
    theme = isDark ? 'dark' : 'light';
    loadGiscus();
  }

  function loadGiscus() {
    const container = document.getElementById('giscus-container');
    if (!container) return;
    container.innerHTML = '';

    const script = document.createElement('script');
    script.src = 'https://giscus.app/client.js';
    script.setAttribute('data-repo', GISCUS_CONFIG.repo);
    script.setAttribute('data-repo-id', GISCUS_CONFIG.repoId);
    script.setAttribute('data-category', GISCUS_CONFIG.category);
    script.setAttribute('data-category-id', GISCUS_CONFIG.categoryId);
    script.setAttribute('data-mapping', GISCUS_CONFIG.mapping);
    script.setAttribute('data-strict', GISCUS_CONFIG.strict);
    script.setAttribute('data-reactions-enabled', GISCUS_CONFIG.reactionsEnabled);
    script.setAttribute('data-emit-metadata', GISCUS_CONFIG.emitMetadata);
    script.setAttribute('data-input-position', GISCUS_CONFIG.inputPosition);
    script.setAttribute('data-lang', GISCUS_CONFIG.lang);
    script.setAttribute('data-theme', theme);
    script.setAttribute('crossorigin', 'anonymous');
    script.async = true;
    container.appendChild(script);
  }

  onMount(() => {
    updateTheme();

    // 监听暗黑模式切换
    const observer = new MutationObserver(() => {
      updateTheme();
    });
    observer.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['class'],
    });

    // swup 页面切换后重新加载
    document.addEventListener('astro:after-swap', updateTheme);

    return () => {
      observer.disconnect();
      document.removeEventListener('astro:after-swap', updateTheme);
    };
  });
</script>

<div class="giscus-wrapper mt-8 rounded-xl">
  <div id="giscus-container"></div>
</div>

<style>
  .giscus-wrapper {
    background: var(--card-bg);
    padding: 1.5rem;
  }
</style>
