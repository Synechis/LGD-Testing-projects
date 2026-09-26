((Drupal, once) => {
  function paneFor(crop) {
    return (
      document.querySelector(
        `details[data-drupal-iwc-id="${CSS.escape(crop)}"]`,
      ) || document.querySelector(`[data-drupal-iwc-id="${CSS.escape(crop)}"]`)
    );
  }

  function tabLinkFor(pane) {
    const id = pane.getAttribute('id');
    if (!id) {
      return null;
    }
    return (
      document.querySelector(
        `.vertical-tabs__menu a[href="#${CSS.escape(id)}"]`,
      ) || document.querySelector(`a[href="#${CSS.escape(id)}"]`)
    );
  }

  function open(crop, scroll) {
    const pane = paneFor(crop);
    if (!pane) {
      return false;
    }
    let parent = pane.parentElement;
    while (parent) {
      if (parent.tagName === 'DETAILS') {
        parent.open = true;
      }
      parent = parent.parentElement;
    }

    document
      .querySelectorAll('.media-assist-crop-target')
      .forEach((el) => el.classList.remove('media-assist-crop-target'));

    const link = tabLinkFor(pane);
    if (link) {
      link.click();
      const item = link.closest('li') || link;
      item.classList.add('media-assist-crop-target');
      if (scroll) {
        item.scrollIntoView({ block: 'center' });
      }
      return true;
    }

    pane.open = true;
    pane.classList.add('media-assist-crop-target');
    if (scroll) {
      pane.scrollIntoView({ block: 'center' });
    }
    return true;
  }

  function isApplied(crop) {
    const pane = paneFor(crop);
    if (!pane) {
      return null;
    }
    const applied = pane.querySelector('[data-drupal-iwc-value=applied]');
    return applied ? applied.value === '1' || applied.value === 1 : null;
  }

  function refresh(summary) {
    const lists = {
      set: summary.querySelector('[data-media-assist-crop-list="set"]'),
      unset: summary.querySelector('[data-media-assist-crop-list="unset"]'),
    };
    if (!lists.set || !lists.unset) {
      return;
    }
    summary
      .querySelectorAll('[data-media-assist-crop-item]')
      .forEach((item) => {
        const applied = isApplied(
          item.getAttribute('data-media-assist-crop-item'),
        );
        if (applied === null) {
          return;
        }
        const target = applied ? lists.set : lists.unset;
        if (item.parentNode !== target) {
          target.appendChild(item);
        }
      });

    const count = summary.querySelector('[data-media-assist-crop-set-count]');
    if (count) {
      count.textContent = String(lists.set.children.length);
    }
    const show = (name, visible) => {
      const el = summary.querySelector(
        `[data-media-assist-crop-empty="${name}"]`,
      );
      if (el) {
        el.hidden = !visible;
      }
    };
    show('set', lists.set.children.length === 0);
    show('unset', lists.unset.children.length === 0);
    show('unset-note', lists.unset.children.length > 0);
  }

  function watch(summary) {
    const update = () => refresh(summary);
    update();
    if (window.jQuery) {
      window.jQuery(document).on('summaryUpdated.mediaAssistCrop', update);
    }
    document.addEventListener('change', update, true);
    document.addEventListener('input', update, true);
    setInterval(update, 1000);
  }

  Drupal.behaviors.mediaAssistCropSummary = {
    attach(context) {
      once(
        'media-assist-crop-live',
        '[data-media-assist-crop-summary]',
        context,
      ).forEach(watch);

      once(
        'media-assist-crop-goto',
        '[data-media-assist-crop-goto]',
        context,
      ).forEach((button) => {
        button.addEventListener('click', () => {
          open(button.getAttribute('data-media-assist-crop-goto'), true);
        });
      });

      once(
        'media-assist-crop-arrived',
        '.media-assist-crop-summary__arrival',
        context,
      ).forEach((el) => {
        const crop = el.getAttribute('data-media-assist-crop-arrived-id');
        if (!crop) {
          return;
        }
        let tries = 0;
        const timer = setInterval(() => {
          tries += 1;
          if (open(crop, true) || tries >= 8) {
            clearInterval(timer);
          }
        }, 150);
      });
    },
  };
})(Drupal, once);
