<?php

declare(strict_types=1);

namespace Drupal\media_assist_embed\Plugin\CKEditor5Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\editor\EditorInterface;
use Drupal\media_assist_embed\Service\ViewModeShapes;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the shape and size selectors for the media embed toolbar.
 */
class ShapeAndSize extends CKEditor5PluginDefault implements ContainerFactoryPluginInterface {

  /**
   * Constructs a ShapeAndSize plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition $plugin_definition
   *   The plugin definition.
   * @param \Drupal\media_assist_embed\Service\ViewModeShapes $shapes
   *   The view mode shapes service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    CKEditor5PluginDefinition $plugin_definition,
    protected ViewModeShapes $shapes,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('media_assist_embed.view_mode_shapes'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $config = $static_plugin_config;
    $format = $editor->getFilterFormat();
    $described = $this->shapes->forFormat($format);
    $filter = $format->filters()->has('media_embed') ? $format->filters('media_embed') : NULL;

    $config['mediaAssistEmbed'] = [
      'viewModes' => array_values($described),
      'defaultViewMode' => $filter->settings['default_view_mode'] ?? NULL,
      'labels' => [
        'shape' => (string) $this->t('Shape'),
        'size' => (string) $this->t('Size'),
        'shapeTitle' => (string) $this->t('Image shape'),
        'sizeTitle' => (string) $this->t('Image size'),
        'other' => (string) $this->t('Other'),
        'landscape' => (string) $this->t('Landscape'),
        'square' => (string) $this->t('Square'),
        'portrait' => (string) $this->t('Portrait'),
        'freestyle' => (string) $this->t('As cropped'),
        'freestyleGroup' => (string) $this->t('No fixed shape'),
        'freestyleHint' => (string) $this->t('Keeps the original aspect ratio.'),
      ],
    ];
    return $config;
  }

}
