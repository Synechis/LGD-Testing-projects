<?php

namespace Drupal\Tests\localgov_services\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Functional tests for LocalGovDrupal install profile.
 */
class ServicesBlockTest extends BrowserTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'localgov_base';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_media',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Install dependencies here to include optional configuration in D11.
    // Note: localgov_core installed seperatly to install body field storage.
    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['localgov_core']);
    $module_installer->install(['localgov_services_landing']);
    $module_installer->install(['localgov_services_page']);
    $module_installer->install(['localgov_services_sublanding']);
    $this->rebuildContainer();
  }

  /**
   * Test blocks display.
   */
  public function testServiceCoreBlocksDisplay() {

    // Check Services CTA block show on landing pages with external link.
    $landing_path = '/' . $this->randomMachineName(8);
    $this->createNode([
      'type' => 'localgov_services_landing',
      'title' => 'Test landing page',
      'body' => [
        'summary' => 'Test landing summary',
        'value' => 'Test landing text',
      ],
      'status' => NodeInterface::PUBLISHED,
      'localgov_common_tasks' => [
        'uri' => 'https://example.com/',
        'title' => 'Example button text',
      ],
      'path' => ['alias' => $landing_path],
    ]);
    $this->drupalGet('/node/1');
    $this->assertSession()->responseContains('href="https://example.com/"');
    $this->assertSession()->pageTextContains('Example button text');

    // Check node title and summary display on service page with internal link.
    $services_page = $this->createNode([
      'type' => 'localgov_services_page',
      'title' => 'Test services page',
      'body' => [
        'summary' => 'Test services page summary',
        'value' => 'Test services page text',
      ],
      'status' => NodeInterface::PUBLISHED,
      'localgov_common_tasks' => [
        'uri' => 'internal:' . $landing_path,
        'title' => 'Landing page link',
      ],
    ]);
    $this->drupalGet('/node/2');
    $this->assertSession()->responseContains($landing_path);
    $this->assertSession()->pageTextContains('Landing page link');

    // Check manually added related links.
    $this->assertSession()->pageTextNotContains('Related Links');
    $services_page->set('localgov_related_links', [
      'uri' => 'http://test.com/',
      'title' => 'Example related link',
    ]);
    $services_page->save();
    $this->drupalGet('/node/2');
    $this->assertSession()->pageTextContains('Related Links');
    $this->assertSession()->responseContains('href="http://test.com/"');
    $this->assertSession()->pageTextContains('Example related link');

    // Check related topics.
    $this->assertSession()->pageTextNotContains('Related Topics');
    $topic_name = $this->randomMachineName(8);
    $topic = Term::create([
      'name' => $topic_name,
      'vid' => 'localgov_topic',
    ]);
    $topic->save();
    $services_page->set('localgov_topic_classified', ['target_id' => $topic->id()]);
    $services_page->set('localgov_hide_related_topics', ['value' => 0]);
    $services_page->save();
    $this->drupalGet('/node/2');
    $this->assertSession()->pageTextContains('Related Topics');
    $this->assertSession()->pageTextContains($topic_name);
    $services_page->set('localgov_hide_related_topics', ['value' => 1]);
    $services_page->save();
    $this->drupalGet('/node/2');
    $this->assertSession()->pageTextNotContains('Related Topics');
    $this->assertSession()->pageTextNotContains($topic_name);
  }

}
