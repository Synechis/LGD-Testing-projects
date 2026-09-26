<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_demo\Functional;

use Drupal\Component\Serialization\Yaml;
use Drupal\Tests\BrowserTestBase;

/**
 * Ensures the files in the module's content directory are installed.
 *
 * @group localgov_demo
 */
class LocalgovDemoContentTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'text',
    'node',
    'user',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Install dependencies first to ensure the field.storage.node.body config
    // is installed before the other modules that depends on it.
    $this->container->get('module_installer')->install(['localgov_core']);
    $this->container->get('module_installer')->install(['localgov_media']);
    $this->container->get('module_installer')->install(['localgov_demo']);
    $this->container = \Drupal::getContainer();
  }

  /**
   * Test content is installed.
   */
  public function testContentInstalled(): void {
    $module_path = $this->container
      ->get('extension.list.module')
      ->getPath('localgov_demo');

    $content_dir = DRUPAL_ROOT . '/' . $module_path . '/content';
    $this->assertDirectoryExists($content_dir, "Content directory missing: $content_dir");

    // Recursively collect all YAML files.
    $files = $this->getYamlFiles($content_dir);

    $total = count($files);
    $this->assertGreaterThan(0, $total, "No YAML files found in content directory: $content_dir");

    $entity_repository = $this->container->get('entity.repository');
    $entity_type_manager = $this->container->get('entity_type.manager');

    $found = 0;

    foreach ($files as $path) {
      $contents = file_get_contents($path);

      $data = Yaml::decode($contents);

      if (empty($data['_meta']['entity_type']) || empty($data['_meta']['uuid'])) {
        continue;
      }

      $entity_type = $data['_meta']['entity_type'];
      $uuid = $data['_meta']['uuid'];

      if (!$entity_type_manager->hasDefinition($entity_type)) {
        continue;
      }

      if ($entity_repository->loadEntityByUuid($entity_type, $uuid)) {
        $found++;
      }
    }

    $required = $total;
    $this->assertGreaterThanOrEqual(
      $required,
      $found,
      "Expected $required of $total content items installed; found $found."
    );
  }

  /**
   * Recursively finds YAML files under a directory.
   */
  protected function getYamlFiles(string $dir): array {
    $files = [];
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
      if ($file->isFile() && $file->getExtension() === 'yml') {
        $files[] = $file->getPathname();
      }
    }

    return $files;
  }

}
