<?php

namespace Drupal\group_alert_banner\Hook;

use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\localgov_alert_banner\Entity\AlertBannerEntityTypeInterface;

/**
 * Entity hook implementations for group_alert_banner.
 */
class EntityHooks {

  /**
   * Implements hook_ENTITY_TYPE_insert().
   *
   * This is necessary during configuration imports.  This makes the plugin
   * definitions from this module immediately available to other config files.
   */
  #[Hook('localgov_alert_banner_type_insert')]
  public static function localgovAlertBannerTypeInsert(AlertBannerEntityTypeInterface $banner_type): void {
    \Drupal::service('group_relation_type.manager')->clearCachedDefinitions();
  }

  /**
   * Implements hook_query_TAG_alter().
   *
   * On group pages, only list group banners belonging to the current group OR
   * site-wide banners.
   */
  #[Hook('query_entity_query_alter')]
  public static function queryEntityQueryAlter(AlterableInterface $query): void {
    if (!$query instanceof SelectInterface) {
      return;
    }
    $entity_type_id = $query->getMetaData('entity_type');
    if ($entity_type_id !== 'localgov_alert_banner' || !$query->hasTag($entity_type_id . '_access')) {
      return;
    }
    $group = \Drupal::service('group.group_route_context')->getGroupFromRoute();
    $gid = empty($group) ? -1 : $group->id();
    if (empty($group) && \Drupal::getContainer()->has('domain_group_resolver')) {
      $gid_from_domain = \Drupal::service('domain_group_resolver')->getActiveDomainGroupId();
      $gid = $gid_from_domain ?? $gid;
    }
    // The 'gcfd' table alias is set in
    // Drupal\group\QueryAccess\EntityQueryAlter::doAlter() for the
    // group_relationship_field_data table.
    $table_aliases = $query->getTables();
    if (array_key_exists('gcfd', $table_aliases)) {
      $query->where('(gcfd.entity_id IS NOT NULL AND gcfd.gid = :gid) OR gcfd.entity_id IS NULL', [
        ':gid' => $gid,
      ]);
    }
  }

}
