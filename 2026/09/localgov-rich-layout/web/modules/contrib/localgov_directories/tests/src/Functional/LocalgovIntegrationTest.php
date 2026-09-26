<?php

namespace Drupal\Tests\localgov_directories\Functional;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\system\Functional\Menu\AssertBreadcrumbTrait;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacets;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacetsType;
use Drupal\node\NodeInterface;

/**
 * Tests pages working together with LocalGov: pathauto, services, search.
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
   * {@inheritdoc}
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
  /**
   * {@inheritdoc}
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
      'localgov_services_landing',
      'localgov_services_sublanding',
      'localgov_services_navigation',
      'localgov_directories',
    ], TRUE);
    $this->rebuildContainer();

    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->adminUser = $this->drupalCreateUser([
      'bypass node access',
      'administer nodes',
    ]);
    $this->nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');

    // To submit a directory we need a facet.
    $type_id = $this->randomMachineName();
    $type = LocalgovDirectoriesFacetsType::create([
      'id' => $type_id,
      'label' => $type_id,
    ]);
    $type->save();
    $facet = LocalgovDirectoriesFacets::create([
      'bundle' => $type_id,
      'title' => $this->randomMachineName(),
    ]);
    $facet->save();
  }

  /**
   * Post overview into a service.
   */
  public function testServicesIntegration() {
    $landing = $this->createNode([
      'title' => 'Landing Page 1',
      'type' => 'localgov_services_landing',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $sublanding = $this->createNode([
      'title' => 'Sublanding 1',
      'type' => 'localgov_services_sublanding',
      'status' => NodeInterface::PUBLISHED,
      'localgov_services_parent' => ['target_id' => $landing->id()],
    ]);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/add/localgov_directory');
    $form = $this->getSession()->getPage();
    $form->fillField('edit-title-0-value', 'Directory 1');
    $form->fillField('edit-body-0-summary', 'Directory 1 summary');
    $form->fillField('edit-body-0-value', 'Directory 1 description');
    $form->fillField('edit-localgov-services-parent-0-target-id', "Sublanding 1 ({$sublanding->id()})");
    $form->checkField('edit-status-value');
    $form->pressButton('edit-submit');

    $this->assertSession()->pageTextContains('Directory 1');
    $trail = ['' => 'Home'];
    $trail += ['landing-page-1' => 'Landing Page 1'];
    $trail += ['landing-page-1/sublanding-1' => 'Sublanding 1'];
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
    $this->createNode([
      'title' => 'Directory 1',
      'type' => 'localgov_directory',
      'status' => NodeInterface::PUBLISHED,
      'body' => $body,
    ]);
    $this->cronRun();

    $this->drupalGet('search', ['query' => ['s' => 'bias+dogma+revelation']]);
    $this->assertSession()->pageTextContains('Directory 1');
    $this->assertSession()->responseContains('<strong>bias</strong>');
    $this->assertSession()->responseContains('<strong>dogma</strong>');
    $this->assertSession()->responseContains('<strong>revelation</strong>');
  }

}
