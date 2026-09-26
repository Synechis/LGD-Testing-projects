<?php

namespace Drupal\Tests\localgov_search_solr\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;

/**
 * Kernel test enabling server.
 *
 * @group localgov_search
 */
class InstallTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'node',
    'search_api',
    'search_api_solr',
    'localgov_search',
    'views',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('search_api_index');
    $this->installEntitySchema('search_api_server');
    $this->installEntitySchema('search_api_task');
    $this->installEntitySchema('node');
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'localgov_search',
      'search_api_solr',
      'search_api',
    ]);

  }

  /**
   * Test enabling/uninstall.
   */
  public function testEnableModule() {
    $index = Index::load('localgov_sitewide_search');
    $this->assertEmpty($index->getServerId());
    $this->assertFalse($index->status());
    \Drupal::service('module_installer')->install(['localgov_search_solr']);
    $index = Index::load('localgov_sitewide_search');
    $this->assertEquals('localgov_sitewide_solr', $index->getServerId());
    $this->assertTrue($index->status());
    \Drupal::service('module_installer')->uninstall(['localgov_search_solr']);
    $index = Index::load('localgov_sitewide_search');
    $this->assertEquals('', $index->getServerId());
    $this->assertFalse($index->status());
  }

}
