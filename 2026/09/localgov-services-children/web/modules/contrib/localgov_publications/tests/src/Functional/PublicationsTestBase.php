<?php

namespace Drupal\Tests\localgov_publications\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base test class for common things.
 */
abstract class PublicationsTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'localgov_base';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_paragraphs',
    'field_ui',
    'path',
    'localgov_media',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('module_installer')->install(['localgov_core']);
    $this->container->get('module_installer')->install(['localgov_publications']);
    $this->rebuildContainer();
  }

}
