<?php

namespace Drupal\localgov_workflows_notifications\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the service contact entity class.
 *
 * @ContentEntityType(
 *   id = "localgov_service_contact",
 *   label = @Translation("Service contact"),
 *   label_collection = @Translation("Service contacts"),
 *   label_singular = @Translation("service contact"),
 *   label_plural = @Translation("service contacts"),
 *   label_count = @PluralTranslation(
 *     singular = "@count service contact",
 *     plural = "@count service contacts",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\localgov_workflows_notifications\LocalgovServiceContactListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\localgov_workflows_notifications\LocalgovServiceContactAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\localgov_workflows_notifications\Form\LocalgovServiceContactForm",
 *       "edit" = "Drupal\localgov_workflows_notifications\Form\LocalgovServiceContactForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "localgov_service_contact",
 *   revision_table = "localgov_service_contact_revision",
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer localgov_service_contact",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *     "revision_user" = "revision_user",
 *   },
 *   links = {
 *     "collection" = "/admin/content/localgov-service-contact",
 *     "add-form" = "/admin/content/localgov-service-contact/add",
 *     "canonical" = "/admin/content/localgov-service-contact/{localgov_service_contact}",
 *     "edit-form" = "/admin/content/localgov-service-contact/{localgov_service_contact}/edit",
 *     "delete-form" = "/admin/content/localgov-service-contact/{localgov_service_contact}/delete",
 *     "delete-multiple-form" = "/admin/content/localgov-service-contact/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.localgov_service_contact.settings",
 * )
 */
final class LocalgovServiceContact extends RevisionableContentEntityBase implements LocalgovServiceContactInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getEmail(): string|null {
    if ($user = $this->getUser()) {
      return $user->getEmail();
    }
    return $this->get('email')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string|null {
    if ($user = $this->getUser()) {
      return $user->getDisplayName();
    }
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser(): AccountInterface|null {
    return $this->get('user')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(): bool {
    return $this->get('enabled')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getName() . ' <' . $this->getEmail() . '>';
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the service contact was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the service contact was last edited.'));

    $fields['author'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The username of the content author.'))
      ->setSetting('target_type', 'user');

    $fields['user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Drupal user'))
      ->setDescription(t('The username of a Drupal user.'))
      ->setSettings([
        'target_type' => 'user',
        'handler_settings' => [
          'include_anonymous' => FALSE,
        ],
      ])
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->addConstraint('UniqueField');

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of service contact.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email address'))
      ->setDescription(t('The email address of the service contact.'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->addConstraint('UniqueField');

    $fields['enabled'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Notifications enabled'))
      ->setDescription(t('Are notifications enabled for the service contact?'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Notifications enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Notes in plain-text format'))
      ->setDisplayOptions('form', [
        'type' => 'text_long',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
