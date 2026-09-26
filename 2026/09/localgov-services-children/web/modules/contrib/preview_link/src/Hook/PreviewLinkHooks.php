<?php

namespace Drupal\preview_link\Hook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\preview_link\PreviewLinkEntityHooks;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\preview_link\PreviewLinkStorageInterface;
use Drupal\preview_link\Routing\PreviewLinkRouteProvider;
use Drupal\preview_link\PreviewLinkUtility;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for preview_link.
 */
class PreviewLinkHooks {

  /**
   * PreviewLinkHooks constructor.
   */
  final public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TimeInterface $time,
  ) {
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types): void {
    $supported_entity_types = array_filter($entity_types, [
      PreviewLinkUtility::class,
      'isEntityTypeSupported',
    ]);
    /** @var \Drupal\Core\Entity\ContentEntityType $type */
    foreach ($supported_entity_types as $type) {
      $providers = $type->getRouteProviderClasses() ?: [];
      if (count($providers['preview_link'] ?? []) === 0) {
        $providers['preview_link'] = PreviewLinkRouteProvider::class;
        $type->setHandlerClass('route_provider', $providers);
      }
      if (!$type->hasLinkTemplate('preview-link-generate')) {
        $type->setLinkTemplate('preview-link-generate', $type->getLinkTemplate('canonical') . '/generate-preview-link');
      }
    }
  }

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    $storage = $this->entityTypeManager->getStorage('preview_link');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('expiry', $this->time->getRequestTime(), '<')
      ->execute();

    // If there are no expired links then nothing to do.
    if (count($ids) === 0) {
      return;
    }

    $previewLinks = $storage->loadMultiple($ids);
    // Simply delete the preview links. A new one will be regenerated at a later
    // date as required.
    $storage->delete($previewLinks);
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'preview_link' => [
        'path' => $path . '/templates',
        'template' => 'preview-link',
        'variables' => [
          'title' => NULL,
          'link' => NULL,
          'description' => NULL,
          'remaining_lifetime' => NULL,
        ],
      ],
    ];
  }

  /**
   * Implements hook_entity_field_access().
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess(string $operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL): AccessResultInterface {
    $storageDefinition = $field_definition->getFieldStorageDefinition();
    if ($storageDefinition->getTargetEntityTypeId() !== 'preview_link') {
      return AccessResult::neutral();
    }
    if ($storageDefinition->getName() === 'entities' && $operation === 'edit') {
      return AccessResult::forbiddenIf(\Drupal::configFactory()->get('preview_link.settings')->get('multiple_entities') !== TRUE)->addCacheTags([
        'config:preview_link.settings',
      ]);
    }
    return AccessResult::neutral();
  }

}
