<?php

namespace Drupal\Tests\localgov_workflows_notifications\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\localgov_workflows_notifications\Entity\LocalgovServiceContact;
use Drupal\node\Entity\Node;

/**
 * Test service contact widget.
 */
class ServiceContactWidgetTest extends BrowserTestBase {

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
   * Service contacts.
   *
   * @var \Drupal\localgov_workflows_notifications\Entity\LocalgovServiceContact[]
   */
  protected $serviceContacts = [];

  /**
   * Service contact Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $drupalUser;

  /**
   * Service contact manual user.
   *
   * @var array
   */
  protected $manualUser = [
    'name' => 'Test User',
    'email' => 'test.user@example.com',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a page content type.
    $this->drupalCreateContentType(['type' => 'page']);

    // Create some service contacts.
    $this->drupalUser = $this->createUser([], $this->randomMachineName());
    $this->serviceContacts[] = LocalgovServiceContact::create([
      'user' => $this->drupalUser->id(),
    ]);
    $this->serviceContacts[0]->save();
    $this->serviceContacts[] = LocalgovServiceContact::create([
      'name' => $this->manualUser['name'],
      'email' => $this->manualUser['email'],
    ]);
    $this->serviceContacts[1]->save();

    $this->drupalLogin($this->rootUser);
  }

  /**
   * Test adding and removing service contacts to a node.
   */
  public function testAddServiceContacts() {

    // Add service contact with Drupal user.
    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();
    $title = $this->randomString();
    $page->fillField('title[0][value]', $title);
    $page->fillField('localgov_service_contacts[0][target_id]', $this->drupalUser->getAccountName());
    $page->pressButton('Save');
    $node = $this->drupalGetNodeByTitle($title);
    $this->assertSame($this->serviceContacts[0]->id(), $node->get('localgov_service_contacts')->getValue()[0]['target_id']);

    // Add service contact with manual user.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $page = $this->getSession()->getPage();
    $page->fillField('localgov_service_contacts[1][target_id]', $this->manualUser['name']);
    $page->pressButton('Save');
    $this->container->get('entity_type.manager')->getStorage('node')->resetCache([$node->id()]);
    $node = Node::load($node->id());
    $this->assertSame($this->serviceContacts[1]->id(), $node->get('localgov_service_contacts')->getValue()[1]['target_id']);

    // Add two service accounts with same name.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $page = $this->getSession()->getPage();
    $page->fillField('localgov_service_contacts[2][target_id]', $this->manualUser['name']);
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('Service contacts must be unique');

    // Add service contact that doesn't exist.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $page->fillField('localgov_service_contacts[2][target_id]', $this->randomString());
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('There are no service contacts matching');

    // Remove service contact.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $page = $this->getSession()->getPage();
    $page->pressButton('localgov_service_contacts_1_remove_button');
    $page->pressButton('localgov_service_contacts_0_remove_button');
    $page->pressButton('Save');
    $this->container->get('entity_type.manager')->getStorage('node')->resetCache([$node->id()]);
    $node = Node::load($node->id());
    $this->assertEmpty($node->get('localgov_service_contacts')->getValue());
  }

}
