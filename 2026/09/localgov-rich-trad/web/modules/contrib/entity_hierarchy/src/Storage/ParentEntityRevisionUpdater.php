<?php

namespace Drupal\entity_hierarchy\Storage;

use Drupal\Core\Entity\ContentEntityInterface;
use PNX\NestedSet\NodeKey;

/**
 * Defines a class for handling a parent entity is updated to a new revision.
 */
class ParentEntityRevisionUpdater extends ParentEntityReactionBase {

  protected static array $originalKeys = [];

  /**
   * Builds a unique key for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity.
   *
   * @return string
   *   Unique key for the entity.
   */
  protected static function buildEntityKey(ContentEntityInterface $entity): string {
    return \sprintf('%s:%s', $entity->getEntityTypeId(), $entity->id());
  }

  /**
   * Static method to prepare for moving child entities.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Parent entity.
   */
  public static function prepareForMovingChildren(ContentEntityInterface $entity): void {
    $key = static::buildEntityKey($entity);
    $unchanged = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId())->loadUnchanged($entity->id());
    if ($unchanged === NULL || $unchanged->getRevisionId() === NULL) {
      return;
    }
    \assert($unchanged instanceof ContentEntityInterface);
    self::$originalKeys[$key] = new NodeKey($entity->id(), $unchanged->getRevisionId());
  }

  /**
   * Move children from old revision to new revision.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $oldRevision
   *   Old revision.
   * @param \Drupal\Core\Entity\ContentEntityInterface $newRevision
   *   New revision.
   */
  public function moveChildren(ContentEntityInterface $oldRevision, ContentEntityInterface $newRevision) {
    if (!$newRevision->isDefaultRevision()) {
      // We don't move children to a non-default revision.
      return;
    }
    if ($newRevision->getRevisionId() == $oldRevision->getRevisionId()) {
      // We don't move anything if the revision isn't changing.
      return;
    }
    if (!$fields = $this->parentCandidate->getCandidateFields($newRevision)) {
      // There are no fields that could point to this entity.
      return;
    }
    // Use the pre-saved original key that references the previous default
    // entity, if that doesn't exist fallback to the saved revision.
    $oldNodeKey = self::$originalKeys[self::buildEntityKey($oldRevision)] ?? $this->nodeKeyFactory->fromEntity($oldRevision);
    $newNodeKey = $this->nodeKeyFactory->fromEntity($newRevision);
    foreach ($fields as $field_name) {
      $this->lockTree($field_name, $newRevision->getEntityTypeId());
      /** @var \Pnx\NestedSet\NestedSetInterface $storage */
      $storage = $this->nestedSetStorageFactory->get($field_name, $newRevision->getEntityTypeId());
      if (!$newParent = $storage->getNode($newNodeKey)) {
        $newParent = $storage->addRootNode($newNodeKey);
      }
      if ($storage->findChildren($oldNodeKey)) {
        $storage->adoptChildren($storage->getNode($oldNodeKey), $newParent);
      }
      $this->releaseLock($field_name, $newRevision->getEntityTypeId());
    }
  }

  /**
   * Get the saved original keys.
   */
  public static function getOriginalKeys(): array {
    return self::$originalKeys;
  }

  /**
   * Flush the saved original keys.
   */
  public static function flushOriginalKeys(): void {
    self::$originalKeys = [];
  }

}
