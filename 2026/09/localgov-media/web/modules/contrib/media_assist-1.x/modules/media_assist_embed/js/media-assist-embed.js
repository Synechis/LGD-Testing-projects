((CKEditor5) => {
  const { Plugin } = CKEditor5.core;
  const { ButtonView, View, ViewModel, addListToDropdown, createDropdown } =
    CKEditor5.ui;
  const { Collection } = CKEditor5.utils;
  const { ShapePanelView } = CKEditor5.mediaAssistEmbed;

  const SHAPE = 'mediaAssistEmbed:shape';
  const SIZE = 'mediaAssistEmbed:size';
  const CORE_VIEW_MODE_ITEM = 'drupalMedia:viewMode';
  const ORIENTATIONS = ['landscape', 'square', 'portrait', 'freestyle'];

  function partition(viewModes) {
    const shaped = [];
    const other = [];
    viewModes.forEach((mode) => {
      if (mode.orientation && mode.band) {
        shaped.push(mode);
      } else {
        other.push(mode);
      }
    });
    return { shaped, other };
  }

  function shapeKey(mode) {
    return `${mode.orientation}:${mode.ratio || ''}`;
  }

  function aspect(shape) {
    if (!shape.ratio) {
      return 1;
    }
    const [w, h] = shape.ratio.split(':').map(Number);
    return h ? w / h : 1;
  }

  function shapesOf(modes) {
    const byKey = new Map();
    modes.forEach((mode) => {
      const key = shapeKey(mode);
      if (!byKey.has(key)) {
        byKey.set(key, {
          key,
          orientation: mode.orientation,
          ratio: mode.ratio,
          modes: [],
        });
      }
      byKey.get(key).modes.push(mode);
    });
    const shapes = [...byKey.values()];
    shapes.forEach((shape) => {
      shape.modes.sort((a, b) => a.band_weight - b.band_weight);
    });
    shapes.sort((a, b) => {
      const oa = ORIENTATIONS.indexOf(a.orientation);
      const ob = ORIENTATIONS.indexOf(b.orientation);
      if (oa !== ob) {
        return oa - ob;
      }
      return aspect(b) - aspect(a);
    });
    return shapes;
  }

  function bandsOf(modes) {
    const bands = new Map();
    modes.forEach((mode) => {
      if (!bands.has(mode.band)) {
        bands.set(mode.band, {
          id: mode.band,
          label: mode.band_label,
          weight: mode.band_weight,
        });
      }
    });
    return [...bands.values()].sort((a, b) => a.weight - b.weight);
  }

  function allowed(shape, bundle) {
    if (!bundle) {
      return true;
    }
    return shape.modes.some((mode) => mode.bundles.includes(bundle));
  }

  function currentViewMode(editor) {
    const command = editor.commands.get('drupalElementStyle');
    const chosen = command && command.value ? command.value.viewMode : null;
    if (chosen) {
      return chosen;
    }
    const styles = editor.config.get('drupalElementStyles.viewMode') || [];
    const fallback = styles.find((style) => style.isDefault);
    return fallback ? fallback.name : null;
  }

  function selectedBundle(editor) {
    const element = editor.model.document.selection.getSelectedElement();
    return element && element.hasAttribute('drupalMediaType')
      ? element.getAttribute('drupalMediaType')
      : null;
  }

  function container(locale, classes, children) {
    const view = new View(locale);
    const collection = view.createCollection();
    children.forEach((child) => collection.add(child));
    view.setTemplate({
      tag: 'div',
      attributes: { class: classes },
      children: collection,
    });
    return view;
  }

  function heading(locale, text) {
    const view = new View(locale);
    view.setTemplate({
      tag: 'span',
      attributes: { class: ['ck', 'mae-shapes__heading'] },
      children: [{ text }],
    });
    return view;
  }

  class MediaAssistEmbed extends Plugin {
    static get pluginName() {
      return 'MediaAssistEmbed';
    }

    init() {
      const { editor } = this;
      const settings = editor.config.get('mediaAssistEmbed');
      if (!settings || !settings.viewModes || !settings.viewModes.length) {
        return;
      }
      const { shaped, other } = partition(settings.viewModes);
      if (!shaped.length) {
        return;
      }

      this.labels = settings.labels || {};
      this.defaultViewMode = settings.defaultViewMode || null;
      this.shapes = shapesOf(shaped);
      this.other = other;
      this.bands = bandsOf(shaped);

      editor.ui.componentFactory.add(SHAPE, (locale) =>
        this._createShapeDropdown(locale),
      );
      editor.ui.componentFactory.add(SIZE, (locale) =>
        this._createSizeDropdown(locale),
      );

      const toolbar = editor.config.get('drupalMedia.toolbar') || [];
      const replaced = [];
      let inserted = false;
      toolbar.forEach((item) => {
        const name =
          typeof item === 'object' && item !== null ? item.name : item;
        if (name === CORE_VIEW_MODE_ITEM) {
          replaced.push(SHAPE, SIZE);
          inserted = true;
          return;
        }
        replaced.push(item);
      });
      if (!inserted) {
        replaced.push(SHAPE, SIZE);
      }
      editor.config.set('drupalMedia.toolbar', replaced);
    }

    _currentShape() {
      const current = currentViewMode(this.editor);
      return (
        this.shapes.find((shape) =>
          shape.modes.some((mode) => mode.id === current),
        ) || null
      );
    }

    _currentBand() {
      const current = currentViewMode(this.editor);
      const shape = this._currentShape();
      if (!shape) {
        return null;
      }
      const mode = shape.modes.find((m) => m.id === current);
      return mode ? mode.band : null;
    }

    _apply(viewMode) {
      const { editor } = this;
      editor.execute('drupalElementStyle', {
        value: viewMode,
        group: 'viewMode',
      });
      editor.editing.view.focus();
    }

    _preferred(modes) {
      return modes.find((mode) => mode.id === this.defaultViewMode) || modes[0];
    }

    _pickShape(shape) {
      const band = this._currentBand();
      const inBand = band && shape.modes.filter((mode) => mode.band === band);
      this._apply(
        this._preferred(inBand && inBand.length ? inBand : shape.modes).id,
      );
    }

    _pickBand(bandId) {
      const shape = this._currentShape() || this.shapes[0];
      const wanted = shape.modes.filter((mode) => mode.band === bandId);
      const nearest = wanted.length
        ? this._preferred(wanted)
        : shape.modes.reduce((best, mode) => {
            const target = this.bands.findIndex((b) => b.id === bandId);
            const distance = Math.abs(mode.band_weight - target);
            return !best || distance < best.distance
              ? { mode, distance }
              : best;
          }, null).mode;
      this._apply(nearest.id);
    }

    _createShapeDropdown(locale) {
      const dropdown = createDropdown(locale);
      const command = this.editor.commands.get('drupalElementStyle');

      dropdown.buttonView.set({
        label: this.labels.shape,
        tooltip: this.labels.shapeTitle,
        withText: true,
      });
      dropdown.buttonView.extendTemplate({
        attributes: { class: ['ck-dropdown__button_label-width_auto'] },
      });
      dropdown.class = 'mae-dropdown';

      const panel = new ShapePanelView(locale, this.labels.shapeTitle);
      const buttons = [];
      const { groups } = panel;
      ORIENTATIONS.forEach((orientation) => {
        const inGroup = this.shapes.filter(
          (shape) => shape.orientation === orientation,
        );
        if (!inGroup.length) {
          return;
        }
        const tiles = inGroup.map((shape) => {
          const button = new ButtonView(locale);
          const label = shape.ratio || this.labels.freestyle;
          button.set({
            label,
            withText: true,
            isToggleable: true,
            tooltip:
              orientation === 'freestyle' ? this.labels.freestyleHint : false,
            class: 'mae-shape',
          });
          button.extendTemplate({
            attributes: {
              style: `--mae-aspect: ${
                shape.ratio ? shape.ratio.replace(':', ' / ') : '4 / 3'
              }`,
              'data-mae-orientation': orientation,
            },
          });
          button.on('execute', () => {
            dropdown.isOpen = false;
            this._pickShape(shape);
          });
          button.shape = shape;
          buttons.push(button);
          panel.addTile(button);
          return button;
        });
        groups.add(
          container(
            locale,
            ['ck', 'mae-shapes__group'],
            [
              heading(
                locale,
                orientation === 'freestyle'
                  ? this.labels.freestyleGroup || this.labels.freestyle
                  : this.labels[orientation] || orientation,
              ),
              container(locale, ['ck', 'mae-shapes__row'], tiles),
            ],
          ),
        );
      });

      if (this.other.length) {
        const others = this.other.map((mode) => {
          const button = new ButtonView(locale);
          button.set({
            label: mode.label,
            withText: true,
            isToggleable: true,
            class: 'mae-other',
          });
          button.on('execute', () => {
            dropdown.isOpen = false;
            this._apply(mode.id);
          });
          button.viewMode = mode.id;
          buttons.push(button);
          panel.addTile(button);
          return button;
        });
        groups.add(
          container(
            locale,
            ['ck', 'mae-shapes__group'],
            [
              heading(locale, this.labels.other),
              container(locale, ['ck', 'mae-shapes__row'], others),
            ],
          ),
        );
      }

      dropdown.panelView.children.add(panel);
      dropdown.focusTracker.add(panel.element);

      const refresh = () => {
        const bundle = selectedBundle(this.editor);
        const current = currentViewMode(this.editor);
        const shape = this._currentShape();
        buttons.forEach((button) => {
          if (button.shape) {
            button.isOn = shape ? button.shape.key === shape.key : false;
            button.isEnabled = allowed(button.shape, bundle);
          } else {
            button.isOn = button.viewMode === current;
          }
        });
        dropdown.buttonView.label = shape
          ? shape.ratio || this.labels.freestyle
          : this.labels.shape;
      };

      dropdown.bind('isEnabled').to(command, 'isEnabled');
      this.listenTo(command, 'change', refresh);
      dropdown.on('change:isOpen', refresh);
      refresh();
      return dropdown;
    }

    _createSizeDropdown(locale) {
      const dropdown = createDropdown(locale);
      const command = this.editor.commands.get('drupalElementStyle');

      dropdown.buttonView.set({
        label: this.labels.size,
        tooltip: this.labels.sizeTitle,
        withText: true,
      });
      dropdown.buttonView.extendTemplate({
        attributes: { class: ['ck-dropdown__button_label-width_auto'] },
      });

      const definitions = new Collection();
      const models = [];
      this.bands.forEach((band) => {
        const model = new ViewModel({
          label: band.label,
          withText: true,
          bandId: band.id,
        });
        models.push(model);
        definitions.add({ type: 'button', model });
      });
      addListToDropdown(dropdown, definitions);

      dropdown.on('execute', (event) => {
        this._pickBand(event.source.bandId);
      });

      const refresh = () => {
        const shape = this._currentShape();
        const band = this._currentBand();
        models.forEach((model) => {
          model.set('isOn', model.bandId === band);
          model.set(
            'class',
            shape && shape.modes.some((mode) => mode.band === model.bandId)
              ? 'mae-size'
              : 'mae-size mae-size--approximate',
          );
        });
        const chosen = this.bands.find((b) => b.id === band);
        dropdown.buttonView.label = chosen ? chosen.label : this.labels.size;
      };

      dropdown.bind('isEnabled').to(command, 'isEnabled');
      this.listenTo(command, 'change', refresh);
      dropdown.on('change:isOpen', refresh);
      refresh();
      return dropdown;
    }
  }

  window.CKEditor5.mediaAssistEmbed.MediaAssistEmbed = MediaAssistEmbed;
})(window.CKEditor5);
