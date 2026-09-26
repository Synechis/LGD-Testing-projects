<?php

namespace Drupal\localgov_workflows_notifications\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\symfony_mailer\EmailFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'localgov_workflows_notifications_email' queue worker.
 *
 * @QueueWorker(
 *   id = "localgov_workflows_notifications_email",
 *   title = @Translation("Email queue worker"),
 *   cron = {"time" = 60}
 * )
 */
class EmailNotificationQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {


  /**
   * Prefix of queue to add notifications to.
   *
   * @var string
   */
  const QUEUE_NAME = 'localgov_workflows_notifications_email';

  /**
   * The email factory service.
   *
   * @var \Drupal\symfony_mailer\EmailFactoryInterface
   */
  protected EmailFactoryInterface $emailFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * Constructs a new EmailNotificationQueueWorker instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EmailFactoryInterface $email_factory, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->emailFactory = $email_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('email_factory'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {

    $type = $data->type;
    $entities = [];
    foreach ($data->entities as $entity) {
      $entities[] = $this->entityTypeManager->getStorage($entity['entity_type'])->load($entity['entity_id']);
    }
    $service_contact = $this->entityTypeManager->getStorage('localgov_service_contact')->load($data->service_contact);

    // Send email to service contact.
    $email = $this->emailFactory->sendTypedEmail('localgov_workflows_notifications', $type, $service_contact, $entities);
    if (!is_null($error = $email->getError())) {
      $this->loggerFactory->get('localgov_workflows_notifications')
        ->error('Failed to send email @type notification to @contact: @error', [
          '@type' => $type,
          '@contact' => $service_contact->label(),
          '@error' => $error,
        ]);
      throw new SuspendQueueException($error);
    }
    $this->loggerFactory->get('localgov_workflows_notifications')
      ->notice('Sent email @type notification to @contact', [
        '@type' => $type,
        '@contact' => $service_contact->label(),
      ]);
  }

}
