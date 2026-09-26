<?php

declare(strict_types=1);

namespace Drupal\preview_link;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityInterface;

/**
 * Determines whether preview links are enabled for an entity type/bundle.
 */
class EntityTypeBundleChecker {

  protected ImmutableConfig $config;

  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->get('preview_link.settings');
  }

  /**
   * Gets the preview_link.settings config, for cache dependency purposes.
   */
  public function getConfig(): ImmutableConfig {
    return $this->config;
  }

  /**
   * Check if the entity type and bundle are enabled.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if enabled, FALSE otherwise.
   */
  public function entityTypeAndBundleEnabled(EntityInterface $entity): bool {
    $enabled_entity_types = $this->config->get('enabled_entity_types');

    // If no entity types are specified, fallback to allowing all.
    if (count($enabled_entity_types) === 0) {
      return TRUE;
    }

    // If the entity type exists in the configuration object.
    if (isset($enabled_entity_types[$entity->getEntityTypeId()])) {
      $enabled_bundles = $enabled_entity_types[$entity->getEntityTypeId()];
      // If no bundles were specified, assume all bundles are enabled.
      if (count($enabled_bundles) === 0) {
        return TRUE;
      }
      // Otherwise fallback to requiring the specific bundle.
      if (in_array($entity->bundle(), $enabled_bundles, TRUE)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
