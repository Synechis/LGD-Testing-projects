((Drupal, drupalSettings, once) => {
  const STORAGE_KEY = 'mediaAssistCropLabelsHidden';

  function styleFromSrc(src) {
    if (!src) {
      return null;
    }
    const after = src.split('?')[0].split('/styles/')[1];
    return after ? after.split('/')[0] : null;
  }

  function label(img) {
    const labels =
      (drupalSettings.mediaAssistCrop &&
        drupalSettings.mediaAssistCrop.styleLabels) ||
      {};
    const name = styleFromSrc(img.currentSrc || img.src);
    if (!name) {
      return null;
    }
    return labels[name] || null;
  }

  function applyHidden() {
    let hidden = false;
    try {
      hidden = window.localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {
      hidden = false;
    }
    document.body.classList.toggle('media-assist-crop-hidden', hidden);
    const toggle = document.querySelector('[data-media-assist-crop-toggle]');
    if (toggle) {
      toggle.setAttribute('aria-pressed', hidden ? 'false' : 'true');
    }
  }

  function addToggle() {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'media-assist-crop-toggle';
    button.setAttribute('data-media-assist-crop-toggle', '');
    button.textContent = Drupal.t('Image style labels');
    button.addEventListener('click', () => {
      try {
        const hidden = window.localStorage.getItem(STORAGE_KEY) === '1';
        window.localStorage.setItem(STORAGE_KEY, hidden ? '0' : '1');
      } catch (e) {
        document.body.classList.toggle('media-assist-crop-hidden');
        return;
      }
      applyHidden();
    });
    document.body.appendChild(button);
  }

  function ensureToggle() {
    if (document.querySelector('[data-media-assist-crop-toggle]')) {
      return;
    }
    addToggle();
    applyHidden();
  }

  function build(el) {
    const img = el.tagName === 'IMG' ? el : el.querySelector('img');
    if (!img) {
      return;
    }
    const found = label(img);
    if (!found) {
      return;
    }
    const anchor = img.closest('picture') || img;
    const parent = anchor.parentNode;
    if (!parent) {
      return;
    }

    const badge = document.createElement('span');
    badge.className = 'media-assist-crop-badge';
    badge.setAttribute('data-media-assist-crop-badge', '');
    badge.textContent = found.short;
    badge.setAttribute(
      'data-media-assist-crop-full',
      found.full || found.short,
    );

    if (getComputedStyle(parent).position === 'static') {
      parent.classList.add('media-assist-crop-anchor');
    }
    ensureToggle();
    parent.appendChild(badge);
  }

  Drupal.behaviors.mediaAssistCropLabels = {
    attach(context) {
      once(
        'media-assist-crop-label',
        '[data-media-assist-crop-style]',
        context,
      ).forEach((el) => {
        const img = el.tagName === 'IMG' ? el : el.querySelector('img');
        if (img && !img.complete) {
          img.addEventListener('load', () => build(el), { once: true });
          return;
        }
        build(el);
      });
    },
  };
})(Drupal, drupalSettings, once);
