/**
 * @file JS file for the preview-link component.
 */

(function previewLinkScript(Drupal) {
  Drupal.behaviors.previewLink = {
    attach(context) {
      const previewLink = context.querySelector(".preview-link__link a");
      const copyLinkToClipboardButton = context.querySelector(
        ".preview-link__copy button"
      );

      copyLinkToClipboardButton.addEventListener("click", function () {
        navigator.clipboard.writeText(previewLink.href);
        copyLinkToClipboardButton.setAttribute("disabled", true);
        copyLinkToClipboardButton.textContent = Drupal.t("Copied!");
        setTimeout(function () {
          copyLinkToClipboardButton.textContent = Drupal.t(
            "Copy link to clipboard"
          );
          copyLinkToClipboardButton.removeAttribute("disabled");
        }, 2000);
      });
    },
  };
})(Drupal);
