<?php

namespace Drupal\Tests\localgov_directories_org\Functional;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\node\NodeInterface;

/**
 * Tests pages working together with LocalGov search.
 *
 * @group localgov_directories
 */
class LocalgovIntegrationTest extends BrowserTestBase {

  use NodeCreationTrait;
  use CronRunTrait;

  /**
   * Skip schema checks.
   *
   * @var string[]
   */
  protected static $configSchemaCheckerExclusions = [
    // Missing schema:
    // - 'content.location.settings.reset_map.position'.
    // - 'content.location.settings.weight'.
    'core.entity_view_display.localgov_geo.area.default',
    'core.entity_view_display.localgov_geo.area.embed',
    'core.entity_view_display.localgov_geo.area.full',
    'core.entity_view_display.geo_entity.area.default',
    'core.entity_view_display.geo_entity.area.embed',
    'core.entity_view_display.geo_entity.area.full',
    // Missing schema:
    // - content.location.settings.geometry_validation.
    // - content.location.settings.multiple_map.
    // - content.location.settings.leaflet_map.
    // - content.location.settings.height.
    // - content.location.settings.height_unit.
    // - content.location.settings.hide_empty_map.
    // - content.location.settings.disable_wheel.
    // - content.location.settings.gesture_handling.
    // - content.location.settings.popup.
    // - content.location.settings.popup_content.
    // - content.location.settings.leaflet_popup.
    // - content.location.settings.leaflet_tooltip.
    // - content.location.settings.map_position.
    // - content.location.settings.weight.
    // - content.location.settings.icon.
    // - content.location.settings.leaflet_markercluster.
    // - content.location.settings.feature_properties.
    'core.entity_form_display.geo_entity.address.default',
    'core.entity_form_display.geo_entity.address.inline',
    // Missing schema:
    // - content.postal_address.settings.providers.
    // - content.postal_address.settings.geocode_geofield.
    'core.entity_form_display.localgov_geo.address.default',
    'core.entity_form_display.localgov_geo.address.inline',
  ];

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
      'localgov_directories_org',
    ], TRUE);
    $this->rebuildContainer();
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
      'title' => 'Directory org 1',
      'type' => 'localgov_directories_org',
      'localgov_directory_channels' => ['target_id' => $directory->id()],
      'body' => $body,
    ]);
    $this->cronRun();

    $this->drupalGet('search', ['query' => ['s' => 'bias+dogma+revelation']]);
    $this->assertSession()->pageTextContains('Directory org 1');
    $this->assertSession()->responseContains('<strong>bias</strong>');
    $this->assertSession()->responseContains('<strong>dogma</strong>');
    $this->assertSession()->responseContains('<strong>revelation</strong>');
  }

}
