<?php

namespace Drupal\Tests\localgov_directories_page\Functional;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\system\Functional\Menu\AssertBreadcrumbTrait;
use Drupal\node\NodeInterface;

/**
 * Tests pages working together with pathauto, services, topics, search.
 *
 * @group localgov_directories
 */
class LocalgovIntegrationTest extends BrowserTestBase {

  use NodeCreationTrait;
  use AssertBreadcrumbTrait;
  use CronRunTrait;

  /**
   * Test breadcrumbs in the Standard profile.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Use stark as tests are not dependent on core markup.
   *
   * @var string
   *
   * @see https://www.drupal.org/node/3083055
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to bypass content access checks.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'media',
    'path',
    'views',
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
    // Run full module installer on the following modules.
    $this->container->get('module_installer')->install([
      'localgov_search',
      'localgov_search_db',
      'localgov_directories',
      'localgov_directories_page',
    ], TRUE);
    $this->rebuildContainer();

    $this->drupalPlaceBlock('system_breadcrumb_block');

    $this->adminUser = $this->drupalCreateUser([
      'bypass node access',
      'administer nodes',
    ]);
    $this->nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');
  }

  /**
   * Post page into a channel.
   */
  public function testDirectoryIntegration() {
    $directory = $this->createNode([
      'title' => 'Directory 1',
      'type' => 'localgov_directory',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $directory->save();

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/add/localgov_directories_page');
    $form = $this->getSession()->getPage();
    $form->fillField('edit-title-0-value', 'Page 1');
    $form->fillField('edit-body-0-summary', 'Page 1 summary');
    $form->fillField('edit-body-0-value', 'Page 1 description');
    $form->selectFieldOption('edit-localgov-directory-channels-primary-' . $directory->id(), $directory->id());
    $form->checkField('edit-status-value');
    $form->pressButton('edit-submit');

    $this->assertSession()->pageTextContains('Page 1');
    $trail = ['' => 'Home'];
    $trail += ['directory-1' => 'Directory 1'];
    $this->assertBreadcrumb(NULL, $trail);
  }

  /**
   * LocalGov Search integration.
   */
  public function testLocalgovSearch() {
    $body = [
      'value' => 'Science is the search for truth, that is the effort to understand the world: it involves the rejection of bias, of dogma, of revelation, but not the rejection of morality.',
      'summary' => 'One of the greatest joys known to man is to take a flight into ignorance in search of knowledge.',
    ];
    // Directory.
    $directory = $this->createNode([
      'title' => 'Directory 1',
      'type' => 'localgov_directory',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->createNode([
      'title' => 'Directory page 1',
      'type' => 'localgov_directories_page',
      'localgov_directory_channels' => ['target_id' => $directory->id()],
      'body' => $body,
    ]);
    $this->cronRun();

    $this->drupalGet('search', ['query' => ['s' => 'bias+dogma+revelation']]);
    $this->assertSession()->pageTextContains('Directory page 1');
    $this->assertSession()->responseContains('<strong>bias</strong>');
    $this->assertSession()->responseContains('<strong>dogma</strong>');
    $this->assertSession()->responseContains('<strong>revelation</strong>');
  }

}
