(function () {
  const toggle = document.querySelector('.nav-toggle');
  const nav = document.querySelector('.topbar nav');
  if (!toggle || !nav) return;

  toggle.addEventListener('click', () => {
    const open = nav.classList.toggle('open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  document.addEventListener('click', (event) => {
    if (!toggle.contains(event.target) && !nav.contains(event.target)) {
      nav.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
    }
  });
})();

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

(function () {
  const chat = document.querySelector('.chat-messages');
  if (chat) {
    chat.scrollTop = chat.scrollHeight;
  }
})();

(function () {
  const phoneInputs = document.querySelectorAll('[data-phone-mask]');
  phoneInputs.forEach((input) => {
    input.addEventListener('input', () => {
      const digits = input.value.replace(/\D/g, '').slice(0, 11);
      if (digits.length <= 10) {
        input.value = digits
          .replace(/^(\d{0,2})(\d{0,4})(\d{0,4}).*/, (_, ddd, parte1, parte2) => {
            let result = ddd ? `(${ddd}` : '';
            if (ddd.length === 2) result += ') ';
            if (parte1) result += parte1;
            if (parte2) result += `-${parte2}`;
            return result;
          });
      } else {
        input.value = digits
          .replace(/^(\d{0,2})(\d{0,5})(\d{0,4}).*/, (_, ddd, parte1, parte2) => {
            let result = ddd ? `(${ddd}` : '';
            if (ddd.length === 2) result += ') ';
            if (parte1) result += parte1;
            if (parte2) result += `-${parte2}`;
            return result;
          });
      }
    });
  });
})();

(function () {
  const fileInput = document.querySelector('[data-image-preview-input]');
  const preview = document.querySelector('[data-image-preview]');
  if (!fileInput || !preview) return;

  fileInput.addEventListener('change', () => {
    preview.innerHTML = '';
    const file = fileInput.files && fileInput.files[0];
    if (!file) {
      preview.hidden = true;
      return;
    }

    const image = document.createElement('img');
    image.alt = 'Pré-visualização da imagem';
    image.src = URL.createObjectURL(file);
    preview.appendChild(image);
    preview.hidden = false;
  });
})();

(function () {
  const codeInput = document.querySelector('input[name="codigo"]');
  if (!codeInput) return;

  codeInput.addEventListener('input', () => {
    codeInput.value = codeInput.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
  });
})();
