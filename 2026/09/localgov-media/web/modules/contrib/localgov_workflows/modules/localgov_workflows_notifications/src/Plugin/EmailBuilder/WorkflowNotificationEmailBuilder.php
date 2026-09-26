<?php

namespace Drupal\localgov_workflows_notifications\Plugin\EmailBuilder;

use Drupal\localgov_workflows_notifications\Entity\LocalgovServiceContactInterface;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\Processor\EmailBuilderBase;
use Drupal\symfony_mailer\Processor\TokenProcessorTrait;

/**
 * Defines the Email Builder plug-in for test mails.
 *
 * @EmailBuilder(
 *   id = "localgov_workflows_notifications",
 *   sub_types = {
 *     "needs_review" = @Translation("Needs review"),
 *   },
 *   common_adjusters = {},
 * )
 */
class WorkflowNotificationEmailBuilder extends EmailBuilderBase {

  use TokenProcessorTrait;

  /**
   * Saves the parameters for a newly created email.
   *
   * @param \Drupal\symfony_mailer\EmailInterface $email
   *   The email to modify.
   * @param \Drupal\localgov_workflows_notifications\Entity\LocalgovServiceContactInterface|null $service_contact
   *   Service contact to notify.
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   Entities to notify about.
   */
  public function createParams(EmailInterface $email, ?LocalgovServiceContactInterface $service_contact = NULL, array $entities = []): void {

    $email->setParam('entities', $entities);
    $email->setParam('service_contact', $service_contact);
  }

  /**
   * {@inheritdoc}
   */
  public function build(EmailInterface $email): void {

    $entities = $email->getParam('entities');
    $service_contact = $email->getParam('service_contact');

    $email->setTo($service_contact->getEmail())
      ->setVariable('date', date('j-M-Y', strtotime('today')))
      ->setVariable('entities', $entities)
      ->setVariable('service_contact', $service_contact);
  }

}
