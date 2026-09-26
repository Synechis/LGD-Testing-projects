<?php

namespace Drupal\localgov_workflows_notifications\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface defining a service contact entity type.
 */
interface LocalgovServiceContactInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the displayed email for the service contact.
   *
   * @return string|null
   *   The email.
   */
  public function getEmail(): string|null;

  /**
   * Gets the display name of the service contact.
   *
   * @return string|null
   *   The name.
   */
  public function getName(): string|null;

  /**
   * Gets the Drupal user associated with service contact.
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   *   The user account.
   */
  public function getUser(): AccountInterface|null;

  /**
   * Are notifications enabled for this service contact?
   *
   * @return bool
   *   True if notifications are enabled.
   */
  public function isEnabled(): bool;

}
