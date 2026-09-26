<?php

namespace Drupal\preview_link;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Interface for auto-populate preview link plugins.
 */
interface PreviewLinkAutopopulateInterface {

  /**
   * Get the entity being previewed.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity.
   */
  public function getEntity(): ?ContentEntityInterface;

  /**
   * Set the entity being previewed.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being previewed.
   *
   * @return static
   *   Returns the plugin instance, for chaining.
   */
  public function setEntity(ContentEntityInterface $entity): static;

  /**
   * Returns the label used for preview link auto-populate buttons.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The label.
   */
  public function getLabel(): TranslatableMarkup|string;

  /**
   * Load the entities that are bing used to populate the preview link form.
   *
   * This method contains the logic required to load all the entities should be
   * added the preview link form.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entities.
   */
  public function getPreviewEntities(): array;

  /**
   * Is the entity being previewed supported by this plugin?
   *
   * @return bool
   *   TRUE if the entity is supported, FALSE otherwise.
   */
  public function isSupported(): bool;

}
