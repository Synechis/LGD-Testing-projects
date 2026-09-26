<?php

namespace Drupal\geo_entity;

use Drupal\geo_entity\Entity\GeoEntity;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Access\AccessResult;

/**
 * Defines the access control handler for the geo entity type.
 */
class GeoEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    assert($entity instanceof GeoEntity);

    // Always allow access if the user has the administrative permission.
    if ($account->hasPermission('administer geo')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Check if the user is the owner of this entity.
    $is_owner = FALSE;
    if ($account->id() && method_exists($entity, 'getOwnerId')) {
      $is_owner = ($account->id() == $entity->getOwnerId());
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission(
          $account,
          'view geo'
        );

      case 'update':
        // If the user has 'edit any geo' permission, they can edit any entity.
        if ($account->hasPermission('edit any geo')) {
          return AccessResult::allowed()
            ->cachePerPermissions();
        }
        // If the user is the owner, check if they can edit their own content.
        if ($is_owner) {
          return AccessResult::allowedIfHasPermission($account, 'edit own geo')
            ->cachePerPermissions()
            ->cachePerUser();
        }
        // If not the owner and doesn't have 'edit any geo', deny access.
        return AccessResult::neutral()
          ->cachePerPermissions()
          ->cachePerUser();

      case 'delete':
        // If the user has 'delete any geo' permission, they can delete any entity.
        if ($account->hasPermission('delete any geo')) {
          return AccessResult::allowed()
            ->cachePerPermissions();
        }
        // If the user is the owner, check if they can delete their own content.
        if ($is_owner) {
          return AccessResult::allowedIfHasPermission($account, 'delete own geo')
            ->cachePerPermissions()
            ->cachePerUser();
        }
        // If not the owner and doesn't have 'delete any geo', deny access.
        return AccessResult::neutral()
          ->cachePerPermissions()
          ->cachePerUser();

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Allow access if user has create permission or admin permission.
    if ($account->hasPermission('administer geo')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return AccessResult::allowedIfHasPermission($account, 'create geo')
      ->cachePerPermissions();
  }

}
