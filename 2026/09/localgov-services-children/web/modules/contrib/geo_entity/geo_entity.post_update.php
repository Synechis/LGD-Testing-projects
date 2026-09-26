<?php

/**
 * @file
 * Post update hooks for geo_entity.
 */

/**
 * Migrate permissions to new 'any' permission format.
 *
 * Updates 'edit geo' to 'edit any geo' and 'delete geo' to 'delete any geo'.
 */
function geo_entity_post_update_permissions() {
  $config_factory = \Drupal::configFactory();
  $permission_map = [
    'edit geo' => 'edit any geo',
    'delete geo' => 'delete any geo',
  ];

  $updated_roles = [];
  $role_names = [];

  // Get all role configurations.
  $role_storage = \Drupal::entityTypeManager()->getStorage('user_role');
  $roles = $role_storage->loadMultiple();

  // Build a map of role IDs to names for reporting.
  foreach ($roles as $rid => $role) {
    $role_names[$rid] = $role->label();
  }

  // Process each role to check for old permissions.
  foreach ($roles as $rid => $role) {
    $updated = FALSE;
    $config = $config_factory->getEditable('user.role.' . $rid);

    if (!$config->isNew()) {
      $permissions = $config->get('permissions') ?: [];

      foreach ($permission_map as $old_permission => $new_permission) {
        if (in_array($old_permission, $permissions)) {
          // Remove the old permission.
          $permissions = array_diff($permissions, [$old_permission]);

          // Add the new permission if it doesn't already exist.
          if (!in_array($new_permission, $permissions)) {
            $permissions[] = $new_permission;
          }

          $updated = TRUE;
        }
      }

      if ($updated) {
        // Save the updated permissions.
        $config->set('permissions', $permissions)->save(TRUE);
        $updated_roles[$rid] = $rid;
      }
    }
  }

  if (empty($updated_roles)) {
    return t('No roles found with the permissions "edit geo" or "delete geo".');
  }

  $role_labels = [];
  foreach (array_keys($updated_roles) as $rid) {
    $role_labels[] = $role_names[$rid] ?? $rid;
  }

  return t('Permissions have been migrated from "edit geo" to "edit any geo" and
    from "delete geo" to "delete any geo" for @count roles: @roles.', [
      '@count' => count($updated_roles),
      '@roles' => implode(', ', $role_labels),
    ]);
}
