<?php

namespace Drupal\localgov_alert_banner\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Gin theme intergration hook implementations for localgov_alert_banner.
 */
class GinHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_gin_content_form_routes().
   */
  #[Hook('gin_content_form_routes')]
  public function ginContentFormRoutes(): array {
    return [
          // Alert banner add form.
      'entity.localgov_alert_banner.add_form',
          // Alert banner add form edit form.
      'entity.localgov_alert_banner.edit_form',
    ];
  }

}
