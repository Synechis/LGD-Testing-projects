<?php

namespace Drupal\Tests\localgov_workflows_notifications\Kernel;

use Drupal\Core\Queue\QueueInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\localgov_workflows_notifications\Entity\LocalgovServiceContact;
use Drupal\localgov_workflows_notifications\NotificationTimerInterface;
use Drupal\localgov_workflows_notifications\Plugin\QueueWorker\EmailNotificationQueueWorker;
use Drupal\node\Entity\Node;

/**
 * Test the cron hook queuing review notifications.
 */
class ReviewNotificationCronHookTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'content_moderation',
    'dynamic_entity_reference',
    'field',
    'filter',
    'node',
    'scheduled_transitions',
    'symfony_mailer',
    'system',
    'text',
    'user',
    'workflows',
    'localgov_workflows',
    'localgov_review_date',
    'localgov_workflows_notifications',
  ];

  /**
   * Node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected Node $node;

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

    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('scheduled_transition');
    $this->installEntitySchema('workflow');
    $this->installEntitySchema('review_date');
    $this->installEntitySchema('localgov_service_contact');
    $this->installSchema('node', ['node_access']);
    $this->installConfig([
      'filter',
      'node',
      'localgov_workflows_notifications',
    ]);

    // Create a service contact.
    $service_contact = LocalgovServiceContact::create([
      'name' => $this->randomMachineName(),
      'email' => $this->randomMachineName() . '@example.com',
      'enabled' => TRUE,
    ]);
    $service_contact->save();

    // Create a node type.
    $this->createContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);

    // Create a node.
    $this->node = $this->createNode([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'localgov_service_contacts' => [
        ['target_id' => $service_contact->id()],
      ],
    ]);

    $this->queue = \Drupal::queue(EmailNotificationQueueWorker::QUEUE_NAME);
    \Drupal::state()->set(NotificationTimerInterface::LAST_RUN, 0);
  }

  /**
   * Test enqueueing nodes for notifications.
   */
  public function testEnqueueNodes() {

    // Check notification queue after cron hook with default state.
    $reviewed = (new \DateTime('1 Jan 2020 12am'))->getTimestamp();
    $review_date = \Drupal::entityTypeManager()->getStorage('review_date')->create([
      'entity' => [
        ['target_id' => $this->node->id()],
      ],
      'review' => $reviewed,
      'active' => TRUE,
      'author' => 1,
    ]);
    $review_date->save();
    localgov_workflows_notifications_cron();
    $this->assertEquals(1, $this->queue->numberOfItems());
    $item = $this->queue->claimItem();
    $this->queue->deleteItem($item);

    // Check notification queue after cron hook with state config.
    $request_time = \Drupal::time()->getRequestTime();
    $reviewed = $request_time - 3600;
    \Drupal::state()->set(NotificationTimerInterface::LAST_RUN, $reviewed - 86400);
    $review_date = \Drupal::entityTypeManager()->getStorage('review_date')->create([
      'entity' => [
        ['target_id' => $this->node->id()],
      ],
      'review' => $reviewed,
      'active' => TRUE,
      'author' => 1,
    ]);
    $review_date->save();
    localgov_workflows_notifications_cron();
    $this->assertEquals(1, $this->queue->numberOfItems());
  }

}
