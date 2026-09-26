<?php

namespace Drupal\localgov_alert_banner\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Module.api hook implementations for localgov_alert_banner.
 */
class ModuleHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled($modules, $is_syncing): void {
    // Configure scheduled transitions if it's being installed.
    if (in_array('scheduled_transitions', $modules, TRUE)) {
      localgov_alert_banner_configure_scheduled_transitions();
    }
  }

}
