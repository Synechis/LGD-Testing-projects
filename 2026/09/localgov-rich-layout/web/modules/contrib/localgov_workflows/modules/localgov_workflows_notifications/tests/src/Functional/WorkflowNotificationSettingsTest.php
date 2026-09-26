<?php

namespace Drupal\Tests\localgov_workflows_notifications\Functional;

use Drupal\Core\State\StateInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\localgov_workflows_notifications\NotificationTimerInterface;

/**
 * Test settings form.
 */
class WorkflowNotificationSettingsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_workflows_notifications',
  ];

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->rootUser);
  }

  /**
   * Test the Localgov Workflows Notifications settings form.
   */
  public function testSettingsForm() {
    $last_run = \Drupal::state()->get(NotificationTimerInterface::LAST_RUN);

    // Test settings get saved.
    $this->drupalGet('/admin/config/workflow/localgov-workflows-notifications');
    $this->submitForm([
      'email_enabled' => FALSE,
      'email_frequency' => 2,
    ], 'Save configuration');
    $settings = \Drupal::config('localgov_workflows_notifications.settings');
    $this->assertFalse($settings->get('email_enabled'));
    $this->assertEquals(2, $settings->get('email_frequency'));

    // Test notification timer gets reset when email notifications are enabled.
    $this->drupalGet('/admin/config/workflow/localgov-workflows-notifications');
    $this->submitForm([
      'email_enabled' => TRUE,
    ], 'Save configuration');
    $new_last_run = \Drupal::state()->get(NotificationTimerInterface::LAST_RUN);
    $this->assertGreaterThan($last_run, $new_last_run);
  }

}
