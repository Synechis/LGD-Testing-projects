<?php

declare(strict_types=1);

namespace Drupal\preview_link\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines preview_link_autopopulate annotation object.
 *
 * @Annotation
 */
class PreviewLinkAutopopulate extends Plugin {

  /**
   * The plugin ID.
   */
  public string $id;

  /**
   * The human-readable name of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public string $title;

  /**
   * The description of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public string $description;

  /**
   * Entities supported by this plugin.
   *
   * An array of entity types as keys with bundles as sub-arrays. If the entity
   * type has bundles or all bundles are supported, then use an empty sub-array.
   *
   * For example, if only the page content type is supported:
   * @code
   * supported_entities = {
   *   "node" = {
   *     "page",
   *   },
   * }
   * @endcode
   *
   * If taxonomy terms are supported:
   * @code
   * supported_entities = {
   *   "taxonomy_term" = {},
   * }
   * @endcode
   */
  public array $supported_entities;

}
