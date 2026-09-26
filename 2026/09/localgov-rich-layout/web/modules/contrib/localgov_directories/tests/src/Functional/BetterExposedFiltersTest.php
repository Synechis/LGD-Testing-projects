<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_directories\Functional;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacets;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacetsType;
use Drupal\node\NodeInterface;

/**
 * Test description.
 *
 * @group localgov_directories
 */
final class BetterExposedFiltersTest extends BrowserTestBase {

  use NodeCreationTrait;
  use CronRunTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE; // phpcs:ignore.

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'better_exposed_filters',
    'facets',
    'facets_exposed_filters',
    'file',
    'search_api',
    'text',
    'media',
    'path',
    'views',
    // LocalGov Media can stay here, its optional config will get
    // installed as the configuration entities are needed.
    'localgov_media',
  ];

  /**
   * Facet group labels.
   *
   * @var array
   */
  protected $groupLabels = [];

  /**
   * Facet labels.
   *
   * Used for reference in each test.
   *
   * @var array
   */
  protected $facetLabels = [];

  /**
   * Facet entities.
   *
   * Used to set facets accross each test.
   *
   * @var array
   */
  protected $facetEntities = [];

  /**
   * Channel node page.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $channelNode;

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
      'localgov_search_db',
      'localgov_directories',
      'localgov_directories_db',
      'localgov_directories_page',
      'localgov_directories_bef_test',
    ], TRUE);
    $this->rebuildContainer();

    // Place the search block (which contains the Bef facets).
    $this->drupalPlaceBlock('localgov_directories_channel_search_block', [
      'context_mapping' => ['node' => '@node.node_route_context:node'],
    ]);

    // Set up facet types.
    $facet_types = [
      'Group 1 ' . $this->randomMachineName(8),
      'Group 2 ' . $this->randomMachineName(8),
    ];
    $this->groupLabels = $facet_types;
    foreach ($facet_types as $type_id) {
      $type = LocalgovDirectoriesFacetsType::create([
        'id' => $type_id,
        'label' => $type_id,
      ]);
      $type->save();
      $facet_type_entities[] = $type;
    }

    // Set up facets.
    $facets = [
      [
        'bundle' => $facet_types[0],
        'title' => 'Facet 1 ' . $this->randomMachineName(8),
      ],
      [
        'bundle' => $facet_types[0],
        'title' => 'Facet 2 ' . $this->randomMachineName(8),
      ],
      [
        'bundle' => $facet_types[1],
        'title' => 'Facet 3' . $this->randomMachineName(8),
      ],
      [
        'bundle' => $facet_types[1],
        'title' => 'Facet 4 ' . $this->randomMachineName(8),
      ],
    ];
    foreach ($facets as $facet_item) {
      $facet = LocalgovDirectoriesFacets::create($facet_item);
      $facet->save();
      $this->facetEntities[] = $facet;
    }
    $this->facetLabels = array_column($facets, 'title');

    // Set up a directory channel and assign the facets to it.
    $body = [
      'value' => 'Science is the search for truth, that is the effort to understand the world: it involves the rejection of bias, of dogma, of revelation, but not the rejection of morality.',
      'summary' => 'One of the greatest joys known to man is to take a flight into ignorance in search of knowledge.',
    ];

    $this->channelNode = $this->createNode([
      'title' => 'Directory channel',
      'type' => 'localgov_directory',
      'status' => NodeInterface::PUBLISHED,
      'body' => $body,
      'localgov_directory_channel_types' => [
        [
          'target_id' => 'localgov_directories_page',
        ],
      ],
      'localgov_directory_facets_enable' => [
        [
          'target_id' => $facet_types[0],
        ],
        [
          'target_id' => $facet_types[1],
        ],
      ],
    ]);
  }

  /**
   * Test the better exposed filters facets are correctly placed on directories.
   */
  public function testBetterExposedFilters(): void {
    $this->createAtestDirectory();

    // Run cron so the directory entries are indexed.
    $this->cronRun();

    // Load the channel node.
    $this->drupalGet($this->channelNode->toUrl()->toString());

    // Verify the facets are correctly split between the groups.
    $query = $this->xpath('.//*[@data-drupal-selector="edit-localgov-directory-facets-filter"]//fieldset');

    // Verify that there are two fieldsets.
    $this->assertCount(2, $query);

    $group_index = 0;
    $facet_index = 0;
    $input_index = 0;
    foreach ($query as $fieldset) {

      // Verify the group legend is a facet type.
      $legend = $fieldset->findAll('css', 'legend');

      // Only one legend should be found.
      $this->assertCount(1, $legend);

      // Check the legend is the correct group label.
      $this->assertEquals($legend[0]->getText(), $this->groupLabels[$group_index]);

      // Check the labels in this group are the facets for that group.
      $labels = $fieldset->findAll('css', 'label');

      // Only 2 facets should be found in each group.
      $this->assertCount(2, $labels);
      foreach ($labels as $label) {

        // Facet label should be 1 and 2 in group 1 and 3 and 4 in group 2.
        $this->assertEquals($label->getText(), $this->facetLabels[$facet_index]);
        $facet_index++;
      }

      // Check the inputs are present.
      $inputs = $fieldset->findAll('css', 'input');

      // Only 2 inputs in each group.
      $this->assertCount(2, $inputs);
      foreach ($inputs as $input) {

        // Check that the input name is for each facet index.
        $this->assertEquals($input->getAttribute('name'), 'localgov_directory_facets_filter[' . $this->facetEntities[$input_index]->id() . ']');
        $input_index++;
      }

      $group_index++;
    }
  }

  /**
   * Sets up a test directory.
   *
   * Creates a directory complete with a 4 directory pages assigned to various
   * LocalGov Directory facet values.
   *
   * @return array
   *   A list of node titles keyed by their node ids.
   */
  public function createAtestDirectory(): array {

    // Set up some directory entries.
    $directory_node_values = [
      // Entry 1 has facet 1 only.
      [
        'title' => 'Entry 1 ' . $this->randomMachineName(8),
        'type' => 'localgov_directories_page',
        'status' => NodeInterface::PUBLISHED,
        'localgov_directory_channels' => [
          [
            'target_id' => $this->channelNode->id(),
          ],
        ],
        'localgov_directory_facets_select' => [
          [
            'target_id' => $this->facetEntities[0]->id(),
          ],
        ],
      ],
      [
        // Entry 2 has facet 2 only.
        'title' => 'Entry 2 ' . $this->randomMachineName(8),
        'type' => 'localgov_directories_page',
        'status' => NodeInterface::PUBLISHED,
        'localgov_directory_channels' => [
          [
            'target_id' => $this->channelNode->id(),
          ],
        ],
        'localgov_directory_facets_select' => [
          [
            'target_id' => $this->facetEntities[1]->id(),
          ],
        ],
      ],
      // Entry 3 has facet 1 and 3.
      [
        'title' => 'Entry 3 ' . $this->randomMachineName(8),
        'type' => 'localgov_directories_page',
        'status' => NodeInterface::PUBLISHED,
        'localgov_directory_channels' => [
          [
            'target_id' => $this->channelNode->id(),
          ],
        ],
        'localgov_directory_facets_select' => [
          [
            'target_id' => $this->facetEntities[0]->id(),
          ],
          [
            'target_id' => $this->facetEntities[2]->id(),
          ],
        ],
      ],
      // Entry 4 has all facets.
      [
        'title' => 'Entry 4 ' . $this->randomMachineName(8),
        'type' => 'localgov_directories_page',
        'status' => NodeInterface::PUBLISHED,
        'localgov_directory_channels' => [
          [
            'target_id' => $this->channelNode->id(),
          ],
        ],
        'localgov_directory_facets_select' => [
          [
            'target_id' => $this->facetEntities[0]->id(),
          ],
          [
            'target_id' => $this->facetEntities[1]->id(),
          ],
          [
            'target_id' => $this->facetEntities[2]->id(),
          ],
          [
            'target_id' => $this->facetEntities[3]->id(),
          ],
        ],
      ],
    ];

    foreach ($directory_node_values as $key => $node_values) {
      $new_node = $this->createNode($node_values);
      $directory_node_values[$key]['nid'] = $new_node->id();
    }

    // Get titles for comparison.
    $node_titles = array_column($directory_node_values, 'title', 'nid');

    return $node_titles;
  }

}
