<?php

namespace Drupal\localgov_alert_banner\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Help hook implementations for localgov_alert_banner.
 */
class HelpHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): string|\Stringable|array|null {
    switch ($route_name) {
      // Main module help for the localgov_alert_banner module.
      case 'help.page.localgov_alert_banner':
        $output = '';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('Alert Banner Support module') . '</p>';
        return $output;

      default:
        return NULL;
    }
  }

}
