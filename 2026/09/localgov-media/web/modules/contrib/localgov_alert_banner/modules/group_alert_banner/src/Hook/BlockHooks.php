<?php

namespace Drupal\group_alert_banner\Hook;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Block hook implementations for group_alert_banner.
 */
class BlockHooks {

  /**
   * Implements hook_block_build_alter().
   *
   * Alterations:
   * - Adds the "route.group" context to alert blocks.  This lets us list
   *   different alert banners in different groups.
   */
  #[Hook('block_build_alter')]
  public static function blockBuildAlter(array &$build, BlockPluginInterface $block_plugin): void {
    $block_type = $block_plugin->getBaseId();
    if ($block_type === 'localgov_alert_banner_block') {
      $build['#cache']['contexts'][] = 'route.group';
    }
  }

}
