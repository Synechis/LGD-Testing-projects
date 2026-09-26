<?php

namespace Drupal\group_alert_banner\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Form hook implementations for group_alert_banner.
 */
class FormHooks {

  /**
   * Implements hook_form_BASE_FORM_ID_alter() for hook_form_localgov_alert_banner_form_alter().
   *
   * Appends a custom form submit handler for the group banner add form.
   */
  #[Hook('form_localgov_alert_banner_form_alter')]
  public static function formLocalgovAlertBannerFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    $is_banner_add_form = strpos($form_id, '_add_form', -9);
    $is_group_context = \Drupal::service('group.group_route_context')->getGroupFromRoute();
    if ($is_banner_add_form && $is_group_context) {
      $form['actions']['submit']['#submit'][] = 'group_alert_banner_restore_publish_page_redirect';
    }
  }

}
