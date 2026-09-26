<?php

namespace Drupal\condition_field\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for condition_field.
 */
class ConditionFieldHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      // Main module help for the condition_field module.
      case 'help.page.condition_field':
        $output = '';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('Defines a field type for Condition Plugins') . '</p>';
        $output .= '<p>' . $this->t('Implemented using the <a href=":conditional-plugins-url">Condition Plugin System</a>.', [
          ':conditional-plugins-url' => 'https://www.drupal.org/node/1961370',
        ]) . '</p>';
        return $output;

      default:
    }
  }

}
