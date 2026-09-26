<?php

namespace Drupal\Tests\localgov_review_date\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\BrowserTestBase;
use Drupal\scheduled_transitions\Form\ScheduledTransitionsSettingsForm;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests that archived nodes no longer appear in review.
 */
class ArchiveNodeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_review_date',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a page content type.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Add page to localgov_editorial workflow.
    $editorial = Workflow::load('localgov_editorial');
    $type = $editorial->getTypePlugin();
    $type->addEntityTypeAndBundle('node', 'page');
    $editorial->save();

    // Configure scheduled transitions.
    $scheduled_transitions_config = \Drupal::service('config.factory')->getEditable('scheduled_transitions.settings');
    $bundles = [
      [
        'entity_type' => 'node',
        'bundle' => 'page',
      ],
    ];
    $scheduled_transitions_config->set('bundles', $bundles);
    $scheduled_transitions_config->save();
    Cache::invalidateTags([
      ScheduledTransitionsSettingsForm::SETTINGS_TAG,
      'config:scheduled_transitions.settings',
    ]);

    // Create test user and log in.
    $web_user = $this->drupalCreateUser([
      'access content',
      'create page content',
      'edit own page content',
      'edit any page content',
      'administer localgov_review_date',
      'use localgov_editorial transition approve',
      'use localgov_editorial transition archive',
      'use localgov_editorial transition archived_draft',
      'use localgov_editorial transition archived_published',
      'use localgov_editorial transition create_new_draft',
      'use localgov_editorial transition publish',
      'use localgov_editorial transition reject',
      'use localgov_editorial transition submit_for_review',
      'view page revisions',
      'view any unpublished content',
      'view all scheduled transitions',
      'add scheduled transitions node page',
      'reschedule scheduled transitions node page',
      'view scheduled transitions node page',
    ]);
    $this->drupalLogin($web_user);
    drupal_flush_all_caches();
  }

  /**
   * Verify that any archived nodes no longer appear in review views.
   */
  public function testArchviedNodeRemovesReview(): void {

    // Create a published node and check the scheduled transition state.
    $this->drupalGet('node/add/page');
    $title = $this->randomMachineName(8);
    $edit = [
      'title[0][value]' => $title,
      'moderation_state[0][state]' => 'published',
      'localgov_review_date[0][reviewed]' => TRUE,
    ];
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($title);

    // This should have a review scheduled transition.
    $this->drupalGet('node/' . $node->id() . '/scheduled-transitions');
    $this->assertSession()->pageTextContains('Review');

    // Archive the page.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $edit = [
      'moderation_state[0][state]' => 'archived',
    ];
    $this->submitForm($edit, 'Save');

    // Should no longer have a scheduled transition.
    $this->drupalGet('node/' . $node->id() . '/scheduled-transitions');
    $this->assertSession()->pageTextNotContains('Review');

    // Create node in the past that should have a review.
    $this->drupalGet('node/add/page');
    $title = $this->randomMachineName(8);
    $edit = [
      'title[0][value]' => $title,
      'moderation_state[0][state]' => 'published',
      'localgov_review_date[0][reviewed]' => TRUE,
      'localgov_review_date[0][review][review_in]' => '12',
      'localgov_review_date[0][review][review_date]' => date('Y-m-d', strtotime('-1 day')),
    ];
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($title);

    // Check page is listed in review.
    $this->drupalGet('admin/content/localgov_review');
    $this->assertSession()->pageTextContains($title);

    // Archive page.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $edit = [
      'moderation_state[0][state]' => 'archived',
    ];
    $this->submitForm($edit, 'Save');

    // Check page is no longer listed as in review.
    $this->drupalGet('admin/content/localgov_review');
    $this->assertSession()->pageTextNotContains($title);
  }

}
