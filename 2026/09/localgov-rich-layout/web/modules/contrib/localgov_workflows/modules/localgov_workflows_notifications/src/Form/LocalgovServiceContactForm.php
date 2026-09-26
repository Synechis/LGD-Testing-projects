<?php

namespace Drupal\localgov_workflows_notifications\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the service contact entity edit forms.
 */
final class LocalgovServiceContactForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Add conditional logic to ensure either user or name and email are set.
    $form['instructions'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => $this->t('Either lookup a Drupal user or enter a name and email address for the service contact.'),
      '#weight' => -10,
    ];
    $form['email']['widget'][0]['value']['#states'] = [
      'enabled' => [
        ':input[name="user[0][target_id]"]' => ['value' => ''],
      ],
      'required' => [
        ':input[name="user[0][target_id]"]' => ['value' => ''],
      ],
    ];
    $form['name']['widget'][0]['value']['#states'] = [
      'enabled' => [
        ':input[name="user[0][target_id]"]' => ['value' => ''],
      ],
      'required' => [
        ':input[name="user[0][target_id]"]' => ['value' => ''],
      ],
    ];
    $form['user']['widget'][0]['target_id']['#states'] = [
      'enabled' => [
        ':input[name="email[0][value]"]' => ['value' => ''],
        'and' => '',
        ':input[name="name[0][value]"]' => ['value' => ''],
      ],
      'required' => [
        ':input[name="email[0][value]"]' => ['value' => ''],
        'and' => '',
        ':input[name="name[0][value]"]' => ['value' => ''],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $service_contact = parent::validateForm($form, $form_state);
    $uid = $form_state->getValue('user')[0]['target_id'];
    $name = $form_state->getValue('name')[0]['value'];
    $email = $form_state->getValue('email')[0]['value'];

    // Ensure either user or name and email are set.
    if ($uid == '' && ($name == '' || $email == '')) {
      $form_state->setError($form['instructions'], $this->t('Either a Drupal user or a name and an email address are required.'));
    }

    if (!is_null($uid)) {
      // Check not anonymous user.
      if ($uid == 0) {
        $form_state->setError($form['user'], $this->t('You cannot associated the Anonymous user with a service contact.'));
      }

      // Check user isn't already associated with a service contact.
      if ($service_contact->isNew()) {
        $service_contacts = $this->entityTypeManager
          ->getStorage('localgov_service_contact')
          ->loadByProperties(['user' => $uid]);
        if (!empty($service_contacts)) {
          $form_state->setError($form['user'], $this->t('The user is already associated with a service contact.'));
        }
      }
    }
    return $service_contact;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $args = ['%label' => $this->entity->label()];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New service contact %label has been created.', $args));
        $this->logger('localgov_workflows_notifications')->notice('New service contact %label has been created.', $args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The service contact %label has been updated.', $args));
        $this->logger('localgov_workflows_notifications')->notice('The service contact %label has been updated.', $args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $result;
  }

}
