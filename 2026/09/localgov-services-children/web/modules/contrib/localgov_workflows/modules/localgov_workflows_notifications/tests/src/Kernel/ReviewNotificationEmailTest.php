<?php

namespace Drupal\Tests\localgov_workflows_notifications\Kernel;

use Drupal\Core\Queue\QueueInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\localgov_workflows_notifications\Entity\LocalgovServiceContact;
use Drupal\localgov_workflows_notifications\Plugin\QueueWorker\EmailNotificationQueueWorker;
use Drupal\symfony_mailer_test\MailerTestTrait;

/**
 * Test the review notification email.
 */
class ReviewNotificationEmailTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use MailerTestTrait;
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
    'symfony_mailer',
    'symfony_mailer_test',
    'system',
    'text',
    'user',
    'localgov_workflows_notifications',
  ];

  /**
   * Service contacts.
   *
   * @var \Drupal\localgov_workflows_notifications\Entity\LocalgovServiceContact
   */
  protected LocalgovServiceContact $serviceContact;

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
    $this->installConfig([
      'filter',
      'node',
      'symfony_mailer',
    ]);

    // Create a service contact.
    $this->serviceContact = LocalgovServiceContact::create([
      'name' => $this->randomMachineName(),
      'email' => $this->randomMachineName() . '@example.com',
      'enabled' => TRUE,
    ]);
    $this->serviceContact->save();

    // Create a node type.
    $this->createContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);

    // Create a node.
    $node = $this->createNode([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'localgov_service_contacts' => [
        ['target_id' => $this->serviceContact->id()],
      ],
    ]);

    // Add notification to queue.
    $this->queue = \Drupal::queue(EmailNotificationQueueWorker::QUEUE_NAME);
    $item = new \stdClass();
    $item->entities[] = [
      'entity_id' => $node->id(),
      'entity_type' => $node->getEntityTypeId(),
    ];
    $item->service_contact = $this->serviceContact->id();
    $item->type = 'needs_review';
    $this->queue->createItem($item);

    // Set from address.
    $this->config('system.site')->set('mail', $this->randomMachineName() . '@example.com')->save();
  }

  /**
   * Test review notification email.
   */
  public function testEnqueueNodes() {

    $this->assertEquals(1, $this->queue->numberOfItems());
    \Drupal::service('cron')->run();
    $this->assertEquals(0, $this->queue->numberOfItems());
    $this->readMail();
    $this->assertNoError();
    $this->assertTo($this->serviceContact->getEmail());
  }

}
