<?php

namespace Drupal\Tests\localgov_services_sublanding\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\node\NodeInterface;

/**
 * Tests localgov services sub-landing pages.
 *
 * @group localgov_services
 */
class ServiceSublandingTest extends BrowserTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field_ui',
    'localgov_media',
  ];

  /**
   * A user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Install dependencies here to include optional configuration in D11.
    // Note: localgov_core installed seperatly to install body field storage.
    $this->container->get('module_installer')->install(['localgov_core']);
    $this->container->get('module_installer')->install(['localgov_services_sublanding']);
    $this->rebuildContainer();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer content types',
      'administer node fields',
    ]);
  }

  /**
   * Test necessary fields have been added.
   */
  public function testServiceSublandingPages() {
    $this->drupalLogin($this->adminUser);

    // Check all fields exist.
    $this->drupalGet('/admin/structure/types/manage/localgov_services_sublanding/fields');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('body');
    $this->assertSession()->pageTextContains('localgov_topics');
    $this->assertSession()->pageTextContains('localgov_services_parent');

    // Check status page.
    $title = $this->randomMachineName(8);
    $summary = $this->randomMachineName(16);
    $body = $this->randomMachineName(32);
    $page = $this->createNode([
      'type' => 'localgov_services_sublanding',
      'title' => $title,
      'body' => [
        'summary' => $summary,
        'value' => $body,
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->drupalGet('/node/' . $page->id());
    $this->assertSession()->pageTextContains($title);
  }

}
