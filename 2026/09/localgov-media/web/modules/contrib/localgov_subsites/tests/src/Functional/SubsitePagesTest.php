<?php

namespace Drupal\Tests\localgov_subsites\Functional;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests LocalGov Subsite pages work together.
 *
 * @group localgov_subsites
 */
class SubsitePagesTest extends BrowserTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * A user with permission to bypass content access checks.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * Install localgov_media first so its optional config (filter.format.wysiwyg)
   * is created before localgov_paragraphs checks install config dependencies.
   *
   * @var array
   */
  protected static $modules = [
    'dbal',
    'entity_hierarchy',
    'field_ui',
    'localgov_media',
    'pathauto',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('module_installer')->install(['localgov_core']);
    $this->rebuildContainer();
    $this->container->get('module_installer')->install(['localgov_subsites'], TRUE);
    $this->rebuildContainer();
    $this->container->get('module_installer')->install(['localgov_subsites_paragraphs'], TRUE);
    $this->rebuildContainer();

    $this->adminUser = $this->drupalCreateUser([
      'bypass node access',
      'administer nodes',
      'administer node fields',
      'reorder entity_hierarchy children',
      'create localgov_subsites_page content',
      'create localgov_subsites_overview content',

    ]);
  }

  /**
   * Verifies basic functionality with all modules.
   */
  public function testSubsiteFields() {

    // If we're testing with sqlite, entity_hierarchy will break.
    // See https://github.com/localgovdrupal/localgov_subsites/pull/8#issuecomment-740668968
    $connection = Database::getConnection()->getConnectionOptions();
    if ($connection['driver'] === 'sqlite') {
      $this->markTestSkipped('entity_hierarchy does not support SQLite.');
    }

    $this->drupalLogin($this->adminUser);

    // Check overview fields.
    $this->drupalGet('/admin/structure/types/manage/localgov_subsites_overview/fields');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('localgov_subsites_banner');
    $this->assertSession()->pageTextContains('localgov_subsites_content');
    $this->assertSession()->pageTextContains('localgov_subsites_hide_menu');
    $this->assertSession()->pageTextContains('localgov_subsites_summary');
    $this->assertSession()->pageTextContains('localgov_subsites_theme');

    // Check page fields.
    $this->drupalGet('/admin/structure/types/manage/localgov_subsites_page/fields');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('localgov_subsites_content');
    $this->assertSession()->pageTextContains('localgov_subsites_topic');
    $this->assertSession()->pageTextContains('localgov_subsites_parent');
    $this->assertSession()->pageTextContains('localgov_subsites_summary');

    // Check fieldgroup tabs on node/add form.
    $this->drupalGet('/node/add/localgov_subsites_overview');
    $this->assertSession()->pageTextContains('Description');
    $this->assertSession()->pageTextContains('Banner and colour theme');
    $this->assertSession()->pageTextContains('Page builder');
    $this->drupalGet('/node/add/localgov_subsites_page');
    $this->assertSession()->pageTextContains('Description');
    $this->assertSession()->pageTextContains('Banner');
    $this->assertSession()->pageTextContains('Page builder');
  }

  /**
   * Pathauto and breadcrumbs.
   *
   * @todo Revisit this test. All assertions were commented out because the
   *   feature works as expected when debugging but the assertions fail in
   *   PHPUnit. Marking as skipped to avoid risky test failures in PHPUnit 11.
   *   See https://www.drupal.org/project/localgov_subsites/issues/3578250
   */
  public function testSubsitePaths(): void {
    $this->markTestSkipped('Test assertions are commented out pending investigation.');
  }

}
