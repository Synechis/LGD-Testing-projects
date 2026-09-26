<?php

namespace Drupal\Tests\localgov_workflows_notifications\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test creating, updating and deleting service contacts.
 */
class ServiceContactCrudTest extends BrowserTestBase {

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
   * A user to edit landing pages.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->user = $this->drupalCreateUser([]);
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Test creating, updating and deleting service contacts.
   */
  public function testServiceContactCrud() {
    $this->drupalGet('admin/content/localgov-service-contact');
    $this->assertSession()->pageTextContains('There are no service contacts yet.');

    // Test creating service contacts with Drupal user.
    $this->drupalGet('admin/content/localgov-service-contact/add');
    $this->submitForm([
      'user[0][target_id]' => $this->user->getAccountName(),
    ], 'Save');
    $this->assertSession()->addressEquals('admin/content/localgov-service-contact');
    $this->assertSession()->statusMessageContains('has been created', 'status');
    $this->assertSession()->pageTextContains($this->user->getDisplayName());
    $this->assertSession()->pageTextContains($this->user->getEmail());
    $this->drupalGet('admin/content/localgov-service-contact/add');
    $this->submitForm([
      'user[0][target_id]' => $this->user->getAccountName(),
    ], 'Save');
    $this->assertSession()->addressEquals('admin/content/localgov-service-contact/add');
    $this->assertSession()->statusMessageContains('The user is already associated with a service contact.', 'error');

    // Test creating service contacts with name and email.
    $name = $this->randomString();
    $email = $this->randomMachineName() . '@example.com';
    $this->drupalGet('admin/content/localgov-service-contact/add');
    $this->submitForm([
      'name[0][value]' => $name,
      'email[0][value]' => $email,
    ], 'Save');
    $this->assertSession()->addressEquals('admin/content/localgov-service-contact');
    $this->assertSession()->statusMessageContains('has been created', 'status');
    $this->assertSession()->pageTextContains($name);
    $this->assertSession()->pageTextContains($email);
    $this->drupalGet('admin/content/localgov-service-contact/add');
    $this->submitForm([
      'name[0][value]' => $name,
      'email[0][value]' => $email,
    ], 'Save');
    $this->assertSession()->addressEquals('admin/content/localgov-service-contact/add');
    $this->assertSession()->statusMessageContains('A service contact with Email address', 'error');

    // Test updating service contacts.
    $this->drupalGet('admin/content/localgov-service-contact');
    $this->assertSession()->pageTextContains('Enabled');
    $this->drupalGet('admin/content/localgov-service-contact/1/edit');
    $this->getSession()->getPage()->uncheckField('enabled[value]');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->addressEquals('admin/content/localgov-service-contact');
    $this->assertSession()->statusMessageContains('has been update', 'status');
    $this->assertSession()->pageTextContains('Disabled');
    $this->assertSession()->pageTextContains('Enabled');
    $this->drupalGet('admin/content/localgov-service-contact/2/edit');
    $this->getSession()->getPage()->uncheckField('enabled[value]');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->addressEquals('admin/content/localgov-service-contact');
    $this->assertSession()->statusMessageContains('has been update', 'status');
    $this->assertSession()->pageTextNotContains('Enabled');

    // Test deleting service contacts.
    $this->drupalGet('admin/content/localgov-service-contact/1/delete');
    $this->getSession()->getPage()->pressButton('Delete');
    $this->assertSession()->addressEquals('admin/content/localgov-service-contact');
    $this->assertSession()->statusMessageContains('has been deleted', 'status');
    $this->drupalGet('admin/content/localgov-service-contact');
    $this->assertSession()->pageTextNotContains($this->user->getDisplayName());
    $this->assertSession()->pageTextNotContains($this->user->getEmail());
    $this->drupalGet('admin/content/localgov-service-contact/2/delete');
    $this->getSession()->getPage()->pressButton('Delete');
    $this->assertSession()->addressEquals('admin/content/localgov-service-contact');
    $this->assertSession()->statusMessageContains('has been deleted', 'status');
    $this->drupalGet('admin/content/localgov-service-contact');
    $this->assertSession()->pageTextNotContains($name);
    $this->assertSession()->pageTextNotContains($email);
  }

}
