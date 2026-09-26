<?php

namespace Drupal\preview_link_test_autopopulate\Plugin\PreviewLinkAutopopulate;

use Drupal\preview_link\PreviewLinkAutopopulatePluginBase;

/**
 * Test autopopulate preview link plugin that loads all nodes.
 *
 * @PreviewLinkAutopopulate(
 *   id = "pages",
 *   label = @Translation("Add all page nodes"),
 *   description = @Translation("Add all page nodes to preview link."),
 *   supported_entities = {
 *     "node" = {
 *       "page",
 *     }
 *   },
 * )
 */
class Pages extends PreviewLinkAutopopulatePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPreviewEntities(): array {

    // Load all pages.
    return $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties(['type' => 'page']);
  }

}
