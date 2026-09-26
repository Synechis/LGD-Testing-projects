<?php

namespace Drupal\localgov_workflows_notifications;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\localgov_workflows_notifications\Plugin\QueueWorker\EmailNotificationQueueWorker;

/**
 * Enqueue a notification to be sent.
 */
class WorkflowNotification implements WorkflowNotificationInterface {

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queueFactory;

  /**
   * Constructs a WorkflowNotification object.
   */
  public function __construct(QueueFactory $queue_factory) {
    $this->queueFactory = $queue_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function enqueue(ContentEntityInterface $entity, string $type): void {
    $queue_name = EmailNotificationQueueWorker::QUEUE_NAME;
    $queue = $this->queueFactory->get($queue_name);

    // Add notifications to service contacts to queue.
    $service_contacts = $entity->get('localgov_service_contacts')->referencedEntities();
    if (!empty($service_contacts)) {
      foreach ($service_contacts as $contact) {

        // Don't queue disabled service contacts.
        if (!$contact->isEnabled()) {
          continue;
        }

        // Aggregate notifications by service contact and type.
        $found = FALSE;
        $claimed_items = [];
        while ($queue_item = $queue->claimItem(1)) {
          if ($queue_item->data->service_contact == $contact->id() && $queue_item->data->type === $type) {

            // Delete old item and create new one with additional entity.
            $queue->deleteItem($queue_item);
            $item = $queue_item->data;
            $item->entities[] = [
              'entity_id' => $entity->id(),
              'entity_type' => $entity->getEntityTypeId(),
            ];
            $queue->createItem($item);
            $found = TRUE;
            break;
          }
          else {
            $claimed_items[] = $queue_item;
          }
        }

        if ($claimed_items) {
          foreach ($claimed_items as $queue_item) {
            $queue->releaseItem($queue_item);
          }
        }

        if (!$found) {

          // Create new item.
          $item = new \stdClass();
          $item->entities[] = [
            'entity_id' => $entity->id(),
            'entity_type' => $entity->getEntityTypeId(),
          ];
          $item->service_contact = $contact->id();
          $item->type = $type;
          $queue->createItem($item);
        }
      }
    }
  }

}
