<?php

declare(strict_types=1);

namespace Drupal\preview_link;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\preview_link\Annotation\PreviewLinkAutopopulate;

/**
 * PreviewLinkAutopopulate plugin manager.
 */
class PreviewLinkAutopopulatePluginManager extends DefaultPluginManager {

  /**
   * Constructs the object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/PreviewLinkAutopopulate', $namespaces, $module_handler, PreviewLinkAutopopulateInterface::class, PreviewLinkAutopopulate::class);
    $this->alterInfo('preview_link_autopopulate_info');
    $this->setCacheBackend($cache_backend, 'preview_link_autopopulate_plugins');
  }

}
