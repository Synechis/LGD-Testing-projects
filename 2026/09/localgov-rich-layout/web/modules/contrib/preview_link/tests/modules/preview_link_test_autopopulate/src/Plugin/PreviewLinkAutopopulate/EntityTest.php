<?php

namespace Drupal\preview_link_test_autopopulate\Plugin\PreviewLinkAutopopulate;

use Drupal\preview_link\PreviewLinkAutopopulatePluginBase;

/**
 * Test autopopulate preview link plugin that loads all entity_test entities.
 *
 * @PreviewLinkAutopopulate(
 *   id = "entity_test",
 *   label = @Translation("Add all entity_test"),
 *   supported_entities = {
 *     "entity_test" = {}
 *   },
 * )
 */
class EntityTest extends PreviewLinkAutopopulatePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPreviewEntities(): array {

    // Load all entity_test entities.
    return $this->entityTypeManager
      ->getStorage('entity_test')
      ->loadMultiple();
  }

}
