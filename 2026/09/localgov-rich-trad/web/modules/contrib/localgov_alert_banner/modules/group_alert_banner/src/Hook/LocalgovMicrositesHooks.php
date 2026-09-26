<?php

namespace Drupal\group_alert_banner\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Localgov drupal microsites hook implementations for group_alert_banner.
 */
class LocalgovMicrositesHooks {

  /**
   * Implements hook_localgov_microsites_roles_default().
   *
   * Defines default site-wide and group-specific Alert banner permissions.
   *
   * @see Drupal\localgov_microsites_group\RolesHelper::getModuleRoles()
   */
  #[Hook('localgov_microsites_roles_default')]
  public static function localgovMicrositesRolesDefault(): array {
    $alert_banner_related_role_permissions = [
      // phpcs:disable Drupal.Classes.FullyQualifiedNamespace
      'global' => [
        // @phpstan-ignore-next-line
        \Drupal\localgov_microsites_group\RolesHelper::MICROSITES_CONTROLLER_ROLE => [
          'access localgov alert banner listing page',
          'use localgov_alert_banners transition create_new_draft',
          'use localgov_alert_banners transition publish',
          'use localgov_alert_banners transition unpublish',
          'view all localgov alert banner entities',
          'view all localgov alert banner entity pages',
        ],
        // @phpstan-ignore-next-line
        \Drupal\localgov_microsites_group\RolesHelper::MICROSITES_EDITOR_ROLE => [
          'access localgov alert banner listing page',
          'use localgov_alert_banners transition create_new_draft',
          'use localgov_alert_banners transition publish',
          'use localgov_alert_banners transition unpublish',
          'view all localgov alert banner entities',
          'view all localgov alert banner entity pages',
        ],
      ],
      'group' => [
        // @phpstan-ignore-next-line
        \Drupal\localgov_microsites_group\RolesHelper::GROUP_ADMIN_ROLE => [
          'access localgov_alert_banner overview',
          'create group_localgov_alert_banner:localgov_alert_banner entity',
          'create group_localgov_alert_banner:localgov_alert_banner relationship',
          'delete any group_localgov_alert_banner:localgov_alert_banner entity',
          'delete own group_localgov_alert_banner:localgov_alert_banner entity',
          'delete any group_localgov_alert_banner:localgov_alert_banner relationship',
          'delete own group_localgov_alert_banner:localgov_alert_banner relationship',
          'update any group_localgov_alert_banner:localgov_alert_banner entity',
          'update own group_localgov_alert_banner:localgov_alert_banner entity',
          'update any group_localgov_alert_banner:localgov_alert_banner relationship',
          'update own group_localgov_alert_banner:localgov_alert_banner relationship',
          'view group_localgov_alert_banner:localgov_alert_banner entity',
          'view group_localgov_alert_banner:localgov_alert_banner relationship',
          'view any unpublished group_localgov_alert_banner:localgov_alert_banner entity',
        ],
        // @phpstan-ignore-next-line
        \Drupal\localgov_microsites_group\RolesHelper::GROUP_MEMBER_ROLE => [
          'access localgov_alert_banner overview',
          'view group_localgov_alert_banner:localgov_alert_banner entity',
        ],
        // @phpstan-ignore-next-line
        \Drupal\localgov_microsites_group\RolesHelper::GROUP_OUTSIDER_ROLE => [
          'view group_localgov_alert_banner:localgov_alert_banner entity',
        ],
        // @phpstan-ignore-next-line
        \Drupal\localgov_microsites_group\RolesHelper::GROUP_ANONYMOUS_ROLE => [
          'view group_localgov_alert_banner:localgov_alert_banner entity',
        ],
      ],
    ];
    return $alert_banner_related_role_permissions;
  }

}
