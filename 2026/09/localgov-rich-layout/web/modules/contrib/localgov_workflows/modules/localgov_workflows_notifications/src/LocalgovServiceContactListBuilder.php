<?php

namespace Drupal\localgov_workflows_notifications;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for the service contact entity type.
 */
final class LocalgovServiceContactListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Service contact');
    $header['email'] = $this->t('Email');
    $header['enabled'] = $this->t('Notifications');
    $header['drupal_user'] = $this->t('Drupal user');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\localgov_workflows_notifications\Entity\LocalgovServiceContactInterface $entity */

    if ($user = $entity->getUser()) {
      $row['name']['data'] = [
        '#theme' => 'username',
        '#account' => $user,
      ];
    }
    else {
      $row['name'] = $entity->getName();
    }
    $row['email'] = $entity->getEmail();
    $row['enabled'] = $entity->get('enabled')->value ? $this->t('Enabled') : $this->t('Disabled');
    $row['drupal_user'] = $user ? '✔' : '';
    return $row + parent::buildRow($entity);
  }

}
