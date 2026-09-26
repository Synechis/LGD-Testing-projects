<?php

namespace Drupal\Tests\localgov_workflows_notifications\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test fields function as expected on the service contact form.
 */
class ServiceContactFieldTest extends WebDriverTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->rootUser);
  }

  /**
   * Check correct fields are enabled and disabled when adding entries.
   */
  public function testAddServiceContactFields() {
    $this->drupalGet('admin/content/localgov-service-contact/add');

    $this->assertSession()->fieldEnabled('user[0][target_id]');
    $this->assertSession()->fieldEnabled('name[0][value]');
    $this->assertSession()->fieldEnabled('email[0][value]');

    $this->getSession()->getPage()->fillField('user[0][target_id]', 'test');
    $this->assertSession()->fieldEnabled('user[0][target_id]');
    $this->assertSession()->fieldDisabled('name[0][value]');
    $this->assertSession()->fieldDisabled('email[0][value]');

    $this->getSession()->getPage()->fillField('user[0][target_id]', '');
    $this->assertSession()->fieldEnabled('name[0][value]');
    $this->assertSession()->fieldEnabled('email[0][value]');

    $this->getSession()->getPage()->fillField('name[0][value]', 'test');
    $this->assertSession()->fieldDisabled('user[0][target_id]');
    $this->assertSession()->fieldEnabled('email[0][value]');

    $this->getSession()->getPage()->fillField('name[0][value]', '');
    $this->getSession()->getPage()->fillField('email[0][value]', 'test');
    $this->assertSession()->fieldDisabled('user[0][target_id]');
    $this->assertSession()->fieldEnabled('name[0][value]');

    $this->getSession()->getPage()->fillField('name[0][value]', 'test');
    $this->getSession()->getPage()->fillField('email[0][value]', 'test');
    $this->assertSession()->fieldDisabled('user[0][target_id]');

    $this->getSession()->getPage()->fillField('name[0][value]', '');
    $this->getSession()->getPage()->fillField('email[0][value]', '');
    $this->getSession()->getPage()->fillField('user[0][target_id]', '');
    $this->assertSession()->fieldEnabled('user[0][target_id]');
    $this->assertSession()->fieldEnabled('name[0][value]');
    $this->assertSession()->fieldEnabled('email[0][value]');
  }

}
