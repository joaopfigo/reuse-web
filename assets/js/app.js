/* ReUse — Interações de frontend */

// ── Menu mobile ─────────────────────────────────────────────
(function () {
  const toggle = document.querySelector('.nav-toggle');
  const nav    = document.querySelector('.topbar nav');
  if (!toggle || !nav) return;

  toggle.addEventListener('click', () => {
    const open = nav.classList.toggle('open');
    toggle.setAttribute('aria-expanded', open);
  });

  // Fecha o menu ao clicar fora
  document.addEventListener('click', (e) => {
    if (!toggle.contains(e.target) && !nav.contains(e.target)) {
      nav.classList.remove('open');
      toggle.setAttribute('aria-expanded', false);
    }
  });
})();

// ── Auto-dismiss de alertas flash (success) ─────────────────
(function () {
  const alerts = document.querySelectorAll('.alert.success');
  alerts.forEach((el) => {
    setTimeout(() => {
      el.style.transition = 'opacity 0.5s ease, max-height 0.5s ease';
      el.style.opacity = '0';
      el.style.maxHeight = '0';
      el.style.overflow = 'hidden';
      el.style.padding = '0';
      el.style.margin = '0';
    }, 4500);
  });
})();

// ── Scroll automático para o final do chat ───────────────────
(function () {
  const chat = document.querySelector('.chat-messages');
  if (chat) chat.scrollTop = chat.scrollHeight;
})();
