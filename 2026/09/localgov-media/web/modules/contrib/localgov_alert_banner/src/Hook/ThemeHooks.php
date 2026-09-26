<?php

namespace Drupal\localgov_alert_banner\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Theme hook implementations for localgov_alert_banner.
 */
class ThemeHooks {

  /**
   * Constructs a new bhcc_alias_node hook definitions.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Current route match.
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   *   Path matcher service.
   */
  public function __construct(
    private readonly RouteMatchInterface $routeMatch,
    private readonly PathMatcherInterface $pathMatcher,
  ) {}

  use StringTranslationTrait;

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    $theme = [];
    $theme['localgov_alert_banner'] = [
      'render element' => 'elements',
      'file' => 'localgov_alert_banner.page.inc',
      'template' => 'localgov-alert-banner',
    ];
    $theme['localgov_alert_banner_content_add_list'] = [
      'render element' => 'content',
      'variables' => [
        'content' => NULL,
      ],
      'file' => 'localgov_alert_banner.page.inc',
    ];
    return $theme;
  }

  /**
   * Implements hook_preprocess_localgov_alert_banner().
   */
  #[Hook('preprocess_localgov_alert_banner')]
  public function preprocessLocalgovAlertBanner(&$variables) : void {
    if (isset($variables['elements']['#localgov_alert_banner'])) {
      // Get token.
      $token = $variables['elements']['#localgov_alert_banner']->getToken();
      // Add a hidden class.
      $variables['attributes']['class'][] = 'hidden';
      // Token as attribute.
      $variables['attributes']['data-dismiss-alert-token'] = $token;
      // Remove the content moderation form if it is present.
      // Do this only on the confirmation form page.
      $route_name = $this->routeMatch->getRouteName();
      if ($route_name == 'entity.localgov_alert_banner.status_form') {
        unset($variables['content']['content_moderation_control']);
      }
      // Set is_front variable.
      try {
        $variables['is_front'] = $this->pathMatcher->isFrontPage();
      }
      catch (\Exception $e) {
        $variables['is_front'] = FALSE;
      }
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_localgov_alert_banner')]
  public function themeSuggestionsLocalgovAlertBanner(array $variables): array {
    $suggestions = [];
    $entity = $variables['elements']['#localgov_alert_banner'];
    $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');
    $suggestions[] = 'localgov_alert_banner__' . $sanitized_view_mode;
    $suggestions[] = 'localgov_alert_banner__' . $entity->bundle();
    $suggestions[] = 'localgov_alert_banner__' . $entity->bundle() . '__' . $sanitized_view_mode;
    $suggestions[] = 'localgov_alert_banner__' . $entity->id();
    $suggestions[] = 'localgov_alert_banner__' . $entity->id() . '__' . $sanitized_view_mode;
    return $suggestions;
  }

  /**
   * Implements hook_preprocess_field().
   */
  #[Hook('preprocess_field')]
  public function preprocessField(&$variables): void {
    if ($variables['element']['#field_name'] == 'link' && $variables['element']['#bundle'] == 'localgov_alert_banner') {
      foreach ($variables['element']['#items'] as $item) {
        if (empty($item->getValue()['title'])) {
          $default_text = $this->t('More information');
          $variables['items'][0]['content']['#title'] = $default_text;
        }
      }
    }
  }

}
