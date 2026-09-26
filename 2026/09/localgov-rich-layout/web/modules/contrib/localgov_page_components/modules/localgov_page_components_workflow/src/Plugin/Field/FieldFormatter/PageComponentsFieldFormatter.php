<?php

namespace Drupal\localgov_page_components_workflow\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs_library\LibraryItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Page components (smart revisions)' formatter.
 */
#[FieldFormatter(
  id: 'localgov_page_components_workflow_formatter',
  label: new TranslatableMarkup('Page components (smart revisions)'),
  field_types: [
    'entity_reference',
  ]
)]
class PageComponentsFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RouteMatchInterface $routeMatch,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $component_storage = $this->entityTypeManager->getStorage('paragraphs_library_item');
    $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
    $component_view_builder = $this->entityTypeManager->getViewBuilder('paragraphs_library_item');

    // Show the latest revision.
    $use_latest = $this->routeMatch->getRouteName() === 'entity.node.latest_version';

    foreach ($items as $delta => $item) {
      $component = $item->entity;

      if (!$component instanceof LibraryItemInterface) {
        continue;
      }

      if (!$use_latest) {
        $published_revision_id = $this->getPublishedRevisionId($component, $component_storage);

        if ($published_revision_id) {
          $component = $component_storage->loadRevision($published_revision_id);
        }

        $component->set('status', 1);

        $elements[$delta] = $component_view_builder->view($component, $this->viewMode);
        continue;
      }

      // Choose components revision.
      $latest_component_revision_id = $component_storage->getLatestRevisionId($component->id());
      if ($latest_component_revision_id) {
        $component = $component_storage->loadRevision($latest_component_revision_id);
      }

      $component->set('status', 1);

      // Process all paragraphs inside this component.
      if ($component->hasField('paragraphs') && !$component->get('paragraphs')->isEmpty()) {
        $this->applyLatestParagraphs($component, $paragraph_storage);
      }

      $elements[$delta] = $component_view_builder->view($component, $this->viewMode);
    }

    return $elements;
  }

  /**
   * Recursively substituting the latest revisions for all Paragraphs.
   *
   * @param \Drupal\paragraphs_library\LibraryItemInterface|\Drupal\paragraphs\ParagraphInterface $entity
   *   The component or paragraph entity that contains paragraphs field.
   * @param \Drupal\Core\Entity\RevisionableStorageInterface $paragraph_storage
   *   Paragraph storage service.
   */
  protected function applyLatestParagraphs(LibraryItemInterface|ParagraphInterface $entity, $paragraph_storage): void {
    foreach ($entity->get('paragraphs') as $delta => $item) {
      $paragraph = $item->entity;

      if (!$paragraph instanceof ParagraphInterface) {
        continue;
      }

      $latest_revision_id = $paragraph_storage->getLatestRevisionId($paragraph->id());
      if ($latest_revision_id) {
        $latest_paragraph = $paragraph_storage->loadRevision($latest_revision_id);
        $latest_paragraph->set('status', 1);

        // Set entity and target_revision_id.
        $entity->get('paragraphs')->get($delta)->entity = $latest_paragraph;
        $entity->get('paragraphs')->get($delta)->target_revision_id = $latest_paragraph->getRevisionId();

        if ($latest_paragraph->hasField('paragraphs') && !$latest_paragraph->get('paragraphs')->isEmpty()) {
          $this->applyLatestParagraphs($latest_paragraph, $paragraph_storage);
        }
      }
    }
  }

  /**
   * Gets the latest published revision ID for a component.
   *
   * @param \Drupal\paragraphs_library\LibraryItemInterface $component
   *   The component entity.
   *
   * @return int|null
   *   The revision ID of the latest published revision, or NULL if none found.
   */
  protected function getPublishedRevisionId(LibraryItemInterface $component): ?int {
    $storage = $this->entityTypeManager->getStorage('paragraphs_library_item');

    $revision_map = $storage->getQuery()
      ->accessCheck(TRUE)
      ->allRevisions()
      ->condition('id', $component->id())
      ->sort('revision_id', 'DESC')
      ->execute();

    if (empty($revision_map)) {
      return NULL;
    }

    $revision_ids = array_keys($revision_map);

    foreach ($revision_ids as $rid) {
      $revision = $storage->loadRevision($rid);

      if (!$revision || !$revision->hasField('moderation_state')) {
        continue;
      }

      if ($revision->get('moderation_state')->value === 'published') {
        return (int) $rid;
      }
    }

    return NULL;
  }

}
