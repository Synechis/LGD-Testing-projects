<?php

namespace Drupal\localgov_page_components_workflow\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs_library\LibraryItemInterface;

/**
 * Defines a class for node hooks.
 *
 * Cascades node workflow states to referenced paragraph library item entities
 * and automates recursive revision synchronization for all nested paragraph
 * structures, ensuring that forward revisions are correctly tracked and staged
 * without breaking content moderation workflows.
 *
 * @package Drupal\localgov_page_components_workflow\Hook
 */
final class NodeHooks {

  use StringTranslationTrait;

  /**
   * Constructs a new NodeHooks instance.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected ThemeManagerInterface $themeManager,
  ) {}

  /**
   * Implements hook_ENTITY_TYPE_update().
   */
  #[Hook('node_update')]
  public function updateNode(NodeInterface $node): void {
    if ($node->hasField('moderation_state') && !$node->get('moderation_state')->isEmpty()) {
      // Get the current moderation state of the node.
      $state = $node->get('moderation_state')->value;

      if ($state) {
        // Process all attached page components.
        if ($node->hasField('localgov_page_components') && !$node->get('localgov_page_components')->isEmpty()) {
          $page_components = $node->get('localgov_page_components')->referencedEntities();

          foreach ($page_components as $component) {
            if ($component instanceof LibraryItemInterface) {
              // Set new revision for component.
              $component->setNewRevision(TRUE);
              $component->set('status', 1);

              // Synchronise moderation state with the parent node.
              // Define the log state label.
              if ($state === 'published') {
                $component->set('moderation_state', 'published');
                $state_label = 'Published';
              }
              elseif (in_array($state, ['draft', 'review'])) {
                $component->set('moderation_state', 'draft');
                $state_label = 'Draft';
              }
              else {
                // Catch-all fallback for other states (e.g., archived) or any
                // custom workflow states implemented on the site.
                $component->set('moderation_state', 'draft');
                $state_label = 'Draft';
              }

              // Set dynamic revision log message.
              $component->setRevisionLogMessage($this->t('Auto-synced with parent node (State: @state)', [
                '@state' => $state_label,
              ]));

              // Process all paragraphs inside this component recursively.
              if ($component->hasField('paragraphs') && !$component->get('paragraphs')->isEmpty()) {
                $this->processParagraphs($component, $state);
              }

              $component->save();
            }
          }
        }
      }
    }
  }

  /**
   * Implements a page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    $admin_theme = $this->configFactory->get('system.theme')->get('admin');
    $current_theme = $this->themeManager->getActiveTheme()->getName();

    if ($current_theme === $admin_theme) {
      $attachments['#attached']['library'][] = 'localgov_page_components_workflow/page_components';
    }
  }

  /**
   * Process all paragraphs inside a component.
   *
   * @param \Drupal\paragraphs_library\LibraryItemInterface $component
   *   The page component object.
   * @param string $state
   *   The node moderated state.
   */
  protected function processParagraphs(LibraryItemInterface $component, string $state): void {
    $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
    $paragraphs = $component->get('paragraphs')->referencedEntities();

    foreach ($paragraphs as $delta => $paragraph) {
      if ($paragraph instanceof ParagraphInterface) {
        $latest_revision_id = $paragraph_storage->getLatestRevisionId($paragraph->id());

        if ($latest_revision_id) {
          /** @var \Drupal\paragraphs\ParagraphInterface $latest */
          $latest = $paragraph_storage->loadRevision($latest_revision_id);

          if ($latest) {
            // Create a new revision for workflow consistency.
            $latest->setNewRevision(TRUE);
            $latest->set('status', 1);
            $latest->save();

            // Update the component reference to use the latest revision.
            $component->get('paragraphs')->get($delta)->target_revision_id = $latest->getRevisionId();
            $this->processNestedParagraphs($latest, $state);
          }
        }
      }
    }
  }

  /**
   * Recursively process nested paragraphs.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph object.
   * @param string $state
   *   The node moderated state.
   */
  protected function processNestedParagraphs(ParagraphInterface $paragraph, string $state): void {
    $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');

    foreach ($paragraph->getFields() as $field) {
      // Only process paragraph reference fields.
      if ($field->getFieldDefinition()->getType() !== 'entity_reference_revisions') {
        continue;
      }

      foreach ($field as $delta => $item) {
        $child = $item->entity;

        if (!$child instanceof ParagraphInterface) {
          continue;
        }

        $latest_revision_id = $paragraph_storage->getLatestRevisionId($child->id());
        if (!$latest_revision_id) {
          continue;
        }

        /** @var \Drupal\paragraphs\ParagraphInterface $latest */
        $latest = $paragraph_storage->loadRevision($latest_revision_id);
        if (!$latest) {
          continue;
        }

        // Deep child paragraphs also remain active (status = 1) to ensure
        // they can render completely during previews or drafts,
        // relying entirely on the root library component for publication
        // gating.
        $latest->setNewRevision(TRUE);
        $latest->set('status', 1);
        $latest->save();

        // Update reference to new revision.
        $field[$delta]->target_revision_id = $latest->getRevisionId();

        // Recursive processing.
        $this->processNestedParagraphs($latest, $state);
      }
    }
  }

}
