<?php

namespace Drupal\localgov_alert_banner_full_page\Hook;

use Drupal\Component\Utility\Html;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Theme hook implementations for localgov_alert_banner_full_page.
 */
class ThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public static function theme(): array {
    $theme = [];
    $theme['localgov_alert_banner__localgov_full_page'] = [
      'base hook' => 'localgov_alert_banner',
    ];
    return $theme;
  }

  /**
   * Implements hook_preprocess_HOOK() for localgov-alert-banner--full.
   *
   * Implements hook_preprocess_localgov_alert_banner() for hook_preprocess_localgov_alert_banner__full().
   *
   * - Adds a unique id for the top level wrapper div of the alert banner.
   * - Adds relevant Javascript settings and library.
   */
  #[Hook('preprocess_localgov_alert_banner__localgov_full_page')]
  public function preprocessLocalgovAlertBannerFullPage(&$variables) : void {
    $alert_banner_id = $variables['attributes']['id'] ?? Html::getUniqueId('localgov-full-page-alert-banner');
    $variables['attributes']['id'] = $alert_banner_id;
    $variables['#attached']['drupalSettings']['localgov_alert_banner_full_page']['localgov_full_page_alert_banner_id'] = $alert_banner_id;
    $variables['#attached']['library'][] = 'localgov_alert_banner_full_page/full_page_alert_banner';
  }

}
