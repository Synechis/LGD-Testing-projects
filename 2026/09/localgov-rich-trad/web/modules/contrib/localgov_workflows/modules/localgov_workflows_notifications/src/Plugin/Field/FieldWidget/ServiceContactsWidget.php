<?php

namespace Drupal\localgov_workflows_notifications\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'service_contacts' field widget.
 *
 * @FieldWidget(
 *   id = "service_contacts_widget",
 *   label = @Translation("Service contacts"),
 *   description = @Translation("An autocomplete service contacts widget."),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
final class ServiceContactsWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $widget = parent::formElement($items, $delta, $element, $form, $form_state);

    $widget['target_id']['#description'] = $this->t('Search for a service contact by name or email.');
    $widget['target_id']['#selection_handler'] = 'service_contact_reference';

    return $widget;
  }

}
