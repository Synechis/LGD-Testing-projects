<?php

namespace Drupal\Tests\localgov_workflows_notifications\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional tests for LocalGov Workflows Notifications access permissions.
 */
class ContentByOwnerAccessTest extends BrowserTestBase {

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
    'localgov_workflows',
    'localgov_workflows_notifications',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('module_installer')->install(['localgov_workflows_notifications']);
    $this->rebuildContainer();
  }

  /**
   * Test the content by owner view is not accessible to anonymous users.
   */
  public function testAnonymousAccess(): void {
    $this->drupalGet('admin/content/localgov-service-contact/content-by-owner');
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);
  }

  /**
   * Test the content by owner view is not accessible to authentcated users.
   */
  public function testAuthenticatedUserAccess(): void {
    $user = $this->drupalCreateUser([]);
    $this->drupalLogin($user);

    $this->drupalGet('admin/content/localgov-service-contact/content-by-owner');
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);
  }

  /**
   * Test the content by owner view is accessible to users with permission.
   */
  public function testAdminAccess(): void {
    $user = $this->drupalCreateUser([
      'administer localgov_service_contact',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('admin/content/localgov-service-contact/content-by-owner');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
  }

}
