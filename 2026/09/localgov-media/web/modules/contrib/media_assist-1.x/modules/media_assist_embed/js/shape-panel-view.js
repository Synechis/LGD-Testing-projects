((CKEditor5) => {
  const { FocusCycler, View } = CKEditor5.ui;
  const { FocusTracker, KeystrokeHandler } = CKEditor5.utils;

  class ShapePanelView extends View {
    constructor(locale, label) {
      super(locale);
      this.focusTracker = new FocusTracker();
      this.keystrokes = new KeystrokeHandler();
      this.tiles = [];
      this.groups = this.createCollection();
      this._focusCycler = new FocusCycler({
        focusables: this.createCollection(),
        focusTracker: this.focusTracker,
        keystrokeHandler: this.keystrokes,
        actions: {
          focusPrevious: ['arrowleft', 'arrowup'],
          focusNext: ['arrowright', 'arrowdown'],
        },
      });
      this.setTemplate({
        tag: 'div',
        attributes: {
          class: ['ck', 'mae-shapes'],
          role: 'group',
          'aria-label': label,
        },
        children: this.groups,
      });
    }

    addTile(button) {
      this.tiles.push(button);
      this._focusCycler.focusables.add(button);
    }

    render() {
      super.render();
      this.tiles.forEach((tile) => this.focusTracker.add(tile.element));
      this.keystrokes.listenTo(this.element);
    }

    focus() {
      const chosen = this.tiles.find((tile) => tile.isOn && tile.isEnabled);
      if (chosen) {
        chosen.focus();
        return;
      }
      this._focusCycler.focusFirst();
    }

    destroy() {
      super.destroy();
      this.focusTracker.destroy();
      this.keystrokes.destroy();
    }
  }

  window.CKEditor5 = window.CKEditor5 || {};
  window.CKEditor5.mediaAssistEmbed = window.CKEditor5.mediaAssistEmbed || {};
  window.CKEditor5.mediaAssistEmbed.ShapePanelView = ShapePanelView;
})(window.CKEditor5);
