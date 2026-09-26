<?php

declare(strict_types=1);

namespace Drupal\preview_link\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\preview_link\Entity\PreviewLinkInterface;
use Drupal\preview_link\EntityTypeBundleChecker;
use Drupal\preview_link\PreviewLinkHostInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Keeps preview links consistent with the entities they unlock.
 */
final class PreviewLinkDeleteHooks {

  use StringTranslationTrait;

  /**
   * Constructs a new PreviewLinkDeleteHooks.
   */
  public function __construct(
    protected PreviewLinkHostInterface $previewLinkHost,
    protected EntityTypeBundleChecker $entityTypeBundleChecker,
    #[Autowire(service: 'logger.channel.preview_link')]
    protected LoggerInterface $logger,
  ) { }

  /**
   * Implements hook_entity_predelete().
   *
   * @see \preview_link_entity_predelete()
   */
  #[Hook('entity_predelete')]
  public function entityPreDelete(EntityInterface $entity): void {
    if (!$this->entityTypeBundleChecker->entityTypeAndBundleEnabled($entity)) {
      return;
    }

    foreach ($this->previewLinkHost->getPreviewLinks($entity) as $previewLink) {
      $this->cleanupPreviewLink($previewLink, $entity);
    }
  }

  /**
   * Removes a deleted entity's reference from a preview link.
   */
  private function cleanupPreviewLink(PreviewLinkInterface $previewLink, EntityInterface $deletedEntity): void {
    try {
      $remainingEntities = array_values(array_filter(
        $previewLink->getEntities(),
        static fn (EntityInterface $referenced): bool => $referenced->getEntityTypeId() !== $deletedEntity->getEntityTypeId() || $referenced->id() !== $deletedEntity->id(),
      ));

      if ($remainingEntities === []) {
        $previewLink->delete();
        $this->logger->info('Deleted preview link %id because its only referenced entity (%type:%entity_id) was deleted.', [
          '%id' => $previewLink->id(),
          '%type' => $deletedEntity->getEntityTypeId(),
          '%entity_id' => $deletedEntity->id(),
        ]);
        return;
      }

      $previewLink->setEntities($remainingEntities)->save();
      $this->logger->info('Removed %type:%entity_id from preview link %id because the entity was deleted. @count entity/entities remain.', [
        '%type' => $deletedEntity->getEntityTypeId(),
        '%entity_id' => $deletedEntity->id(),
        '%id' => $previewLink->id(),
        '@count' => count($remainingEntities),
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to clean up preview link %id after %type:%entity_id was deleted: %message', [
        '%id' => $previewLink->id(),
        '%type' => $deletedEntity->getEntityTypeId(),
        '%entity_id' => $deletedEntity->id(),
        '%message' => $e->getMessage(),
      ]);
    }
  }

}
