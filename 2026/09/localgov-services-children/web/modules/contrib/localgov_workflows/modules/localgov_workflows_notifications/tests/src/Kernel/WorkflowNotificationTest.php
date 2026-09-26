<?php

namespace Drupal\Tests\localgov_workflows_notifications\Kernel;

use Drupal\Core\Queue\QueueInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\localgov_workflows_notifications\Entity\LocalgovServiceContact;
use Drupal\localgov_workflows_notifications\Plugin\QueueWorker\EmailNotificationQueueWorker;
use Drupal\localgov_workflows_notifications\WorkflowNotification;

/**
 * Test the workflow notification service.
 */
class WorkflowNotificationTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'filter',
    'node',
    'system',
    'text',
    'user',
    'localgov_workflows_notifications',
  ];

  /**
   * Service contacts.
   *
   * @var \Drupal\localgov_workflows_notifications\Entity\LocalgovServiceContact[]
   */
  protected array $serviceContacts = [];

  /**
   * The workflow notification service.
   *
   * @var \Drupal\localgov_workflows_notifications\WorkflowNotification
   */
  protected WorkflowNotification $notifier;

  /**
   * Email queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected QueueInterface $queue;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('localgov_service_contact');
    $this->installConfig('filter');
    $this->installConfig('node');

    // Create some service contacts.
    $this->serviceContacts['enabled'] = LocalgovServiceContact::create([
      'name' => $this->randomMachineName(),
      'email' => $this->randomMachineName() . '@example.com',
      'enabled' => TRUE,
    ]);
    $this->serviceContacts['enabled']->save();
    $this->serviceContacts['disabled'] = LocalgovServiceContact::create([
      'name' => $this->randomMachineName(),
      'email' => $this->randomMachineName() . '@example.com',
      'enabled' => FALSE,
    ]);
    $this->serviceContacts['disabled']->save();
    $this->serviceContacts['another'] = LocalgovServiceContact::create([
      'name' => $this->randomMachineName(),
      'email' => $this->randomMachineName() . '@example.com',
      'enabled' => TRUE,
    ]);
    $this->serviceContacts['another']->save();

    // Create a node type.
    $this->createContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);

    // Set services.
    $this->notifier = $this->container->get('localgov_workflows_notifications.notifier');
    $this->queue = \Drupal::queue(EmailNotificationQueueWorker::QUEUE_NAME);
  }

  /**
   * Test enqueueing nodes for notifications.
   */
  public function testEnqueueNodes() {

    // Enqueue a notification.
    $node1 = $this->createNode([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'localgov_service_contacts' => [
        ['target_id' => $this->serviceContacts['enabled']->id()],
      ],
    ]);
    $this->notifier->enqueue($node1, 'review');
    $this->assertEquals(1, $this->queue->numberOfItems());
    $item = $this->queue->claimItem(0);
    $this->assertEquals(1, count($item->data->entities));
    $this->assertEquals($this->serviceContacts['enabled']->id(), $item->data->service_contact);
    $this->assertEquals('review', $item->data->type);
    $this->queue->deleteItem($item);

    // Enqueue a notification with disabled service contact.
    $node2 = $this->createNode([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'localgov_service_contacts' => [
        ['target_id' => $this->serviceContacts['disabled']->id()],
      ],
    ]);
    $this->notifier->enqueue($node2, 'review');
    $this->assertEquals(0, $this->queue->numberOfItems());

    // Enqueue a notification with same service contact.
    $node3 = $this->createNode([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'localgov_service_contacts' => [
        ['target_id' => $this->serviceContacts['enabled']->id()],
      ],
    ]);
    $this->notifier->enqueue($node1, 'review');
    $this->notifier->enqueue($node3, 'review');
    $this->assertEquals(1, $this->queue->numberOfItems());
    $item = $this->queue->claimItem(0);
    $this->assertEquals(2, count($item->data->entities));
    $this->queue->releaseItem($item);
    $item = $this->queue->claimItem(0);
    $this->queue->deleteItem($item);

    // Enqueue a notification with no service contact.
    $node4 = $this->createNode([
      'type' => 'page',
      'title' => $this->randomMachineName(),
    ]);
    $this->notifier->enqueue($node4, 'review');
    $this->assertEquals(0, $this->queue->numberOfItems());

    // Enqueue a notification to multiple service contacts.
    $node5 = $this->createNode([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'localgov_service_contacts' => [
        ['target_id' => $this->serviceContacts['enabled']->id()],
        ['target_id' => $this->serviceContacts['another']->id()],
      ],
    ]);
    $this->notifier->enqueue($node5, 'published');
    $this->assertEquals(2, $this->queue->numberOfItems());

    // Ensure notifications are not duplicated.
    $node6 = $this->createNode([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'localgov_service_contacts' => [
        ['target_id' => $this->serviceContacts['enabled']->id()],
      ],
    ]);
    $this->notifier->enqueue($node6, 'published');
    $this->assertEquals(2, $this->queue->numberOfItems());

    // Enqueue a notification with different type.
    $node7 = $this->createNode([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'localgov_service_contacts' => [
        ['target_id' => $this->serviceContacts['enabled']->id()],
      ],
    ]);
    $this->notifier->enqueue($node7, 'unpublished');
    $this->assertEquals(3, $this->queue->numberOfItems());
  }

}
