<?php

namespace Drupal\localgov_workflows_notifications;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the service contact entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class LocalgovServiceContactAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    return match($operation) {
      'view' => AccessResult::allowedIfHasPermissions($account, ['view localgov_service_contact', 'administer localgov_service_contact'], 'OR'),
      'update' => AccessResult::allowedIfHasPermissions($account, ['edit localgov_service_contact', 'administer localgov_service_contact'], 'OR'),
      'delete' => AccessResult::allowedIfHasPermissions($account, ['delete localgov_service_contact', 'administer localgov_service_contact'], 'OR'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create localgov_service_contact', 'administer localgov_service_contact'], 'OR');
  }

}
