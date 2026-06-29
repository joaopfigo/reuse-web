(function () {
  const currentScript = document.currentScript;
  const appBase = currentScript
    ? currentScript.src.replace(/\/assets\/js\/pwa\.js(?:\?.*)?$/, '')
    : window.location.origin;

  if (!document.querySelector('link[rel="manifest"]')) {
    const manifest = document.createElement('link');
    manifest.rel = 'manifest';
    manifest.href = `${appBase}/manifest.webmanifest`;
    document.head.appendChild(manifest);
  }

  if (!document.querySelector('meta[name="theme-color"]')) {
    const theme = document.createElement('meta');
    theme.name = 'theme-color';
    theme.content = '#b45d7f';
    document.head.appendChild(theme);
  }

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register(`${appBase}/service-worker.js`, { scope: `${appBase}/` }).catch(() => {});
    });
  }

  const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
  const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
  if (isStandalone || !isMobile || localStorage.getItem('reuse-install-dismissed') === '1') {
    return;
  }

  let deferredPrompt = null;

  const createBanner = (mode) => {
    if (document.querySelector('[data-install-banner]')) return;

    const banner = document.createElement('div');
    banner.className = 'install-app-card';
    banner.dataset.installBanner = 'true';
    banner.innerHTML = `
      <div>
        <strong>Usar ReUse como app</strong>
        <span>${mode === 'ios' ? 'No iPhone, toque em compartilhar e escolha "Adicionar à Tela de Início".' : 'Instale o atalho do ReUse na tela inicial.'}</span>
      </div>
      <button type="button" class="btn primary" data-install-action>${mode === 'ios' ? 'Entendi' : 'Instalar'}</button>
      <button type="button" class="install-dismiss" aria-label="Fechar aviso" data-install-dismiss>×</button>
    `;

    banner.querySelector('[data-install-dismiss]').addEventListener('click', () => {
      localStorage.setItem('reuse-install-dismissed', '1');
      banner.remove();
    });

    banner.querySelector('[data-install-action]').addEventListener('click', async () => {
      if (deferredPrompt) {
        deferredPrompt.prompt();
        await deferredPrompt.userChoice.catch(() => null);
        deferredPrompt = null;
      }
      if (mode === 'ios') {
        localStorage.setItem('reuse-install-dismissed', '1');
      }
      banner.remove();
    });

    document.body.appendChild(banner);
  };

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;
    createBanner('android');
  });

  window.addEventListener('load', () => {
    const isIos = /iPhone|iPad|iPod/i.test(navigator.userAgent);
    if (isIos) {
      setTimeout(() => createBanner('ios'), 1200);
    }
  });
})();
