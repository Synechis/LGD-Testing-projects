<?php

namespace Drupal\Tests\localgov_events\Functional;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\date_recur\DateRecurOccurrences;

/**
 * Tests infinite events are correctly extended on cron.
 *
 * @group localgov_events
 */
class InfiniteEventTest extends BrowserTestBase {

  use CronRunTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE; // phpcs:ignore.

  /**
   * Test using the minimal profile.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Test using the stark theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'file',
    'path',
    'field_ui',
    'localgov_media',
    // LocalGov Media can stay here, its optional config will get
    // installed as the configuration entities are needed.
    'localgov_media',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('module_installer')->install(['localgov_core']);
    $this->assertSame('text_with_summary', FieldStorageConfig::loadByName('node', 'body')->getType());
    $this->assertNotEmpty($this->config('core.entity_view_mode.media.localgov_featured')->get('id'));
    $this->container->get('module_installer')->install(['localgov_events',], TRUE);
    $this->rebuildContainer();
  }

  /**
   * Test that an infinite event is correctly extended on cron.
   */
  public function testInfiniteEvent(): void {
    $title = $this->randomMachineName(8);
    $node = $this->createNode([
      'title' => 'localgov_event ' . $title,
      'type' => 'localgov_event',
      'body' => 'LGD Event test page',
      'status' => 1,
      'localgov_event_date' => $this->getInfiniteDate(),
    ]);

    // Delete future infinite events from the cached table to ensure we are
    // testing realistic sceanrio (event saved in the far past).
    // Get the table name for the date_recur occurrences.
    $fieldStorageDefinitions = $this->container->get('entity_field.manager')->getFieldStorageDefinitions('node');
    $occurrenceTableName = DateRecurOccurrences::getOccurrenceCacheStorageTableName($fieldStorageDefinitions['localgov_event_date']);
    $this->container->get('database')->delete($occurrenceTableName)->condition('entity_id', $node->id())->condition('localgov_event_date_value', date('Y-m-d H:i:s', strtotime('now')), '>=')->execute();

    // Check the future records are gone.
    $this->drupalGet('/events');
    $this->assertSession()->pageTextNotContains($title);

    // Run cron.
    $this->cronRun();

    // Check the future records are now present.
    $this->drupalGet('/events');
    $this->assertSession()->pageTextContains($title);

  }

  /**
   * Creates a 10 day recurring event.
   *
   * Starts 2 days ago and
   * continues for 10 days.
   */
  private function getInfiniteDate(): array {

    $date = new DrupalDateTime('-5 Years', 'UTC');
    $date->modify('midnight');
    $start_date = $date->modify("-2 days")->format('Y-m-d\TH:i:s');
    $end_date = $date->modify("+2 hours")->format('Y-m-d\TH:i:s');
    return [
      'value' => $start_date,
      'end_value' => $end_date,
      'timezone' => 'UTC',
      'infinite' => 1,
      'rrule' => 'FREQ=MONTHLY',
    ];
  }

}
