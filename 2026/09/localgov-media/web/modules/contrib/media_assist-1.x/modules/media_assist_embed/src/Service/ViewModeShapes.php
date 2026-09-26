<?php

declare(strict_types=1);

namespace Drupal\media_assist_embed\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\breakpoint\BreakpointManagerInterface;
use Drupal\filter\FilterFormatInterface;
use Drupal\media_assist\ImageStyleReader;

/**
 * Provides the shape and size of media view modes.
 */
class ViewModeShapes {

  use StringTranslationTrait;

  /**
   * The media source field names, keyed by bundle.
   *
   * @var array
   */
  protected array $sourceFields = [];

  /**
   * Constructs a ViewModeShapes.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\breakpoint\BreakpointManagerInterface $breakpointManager
   *   The breakpoint manager.
   * @param \Drupal\media_assist\ImageStyleReader $reader
   *   The image style reader.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected BreakpointManagerInterface $breakpointManager,
    protected ImageStyleReader $reader,
  ) {}

  /**
   * Returns the shape and size of the view modes allowed by a text format.
   *
   * @param \Drupal\filter\FilterFormatInterface $format
   *   The text format.
   *
   * @return array
   *   An array keyed by view mode ID, as returned by forViewModes(), or an
   *   empty array if the format has no enabled media embed filter.
   */
  public function forFormat(FilterFormatInterface $format): array {
    $filter = $format->filters()->has('media_embed') ? $format->filters('media_embed') : NULL;
    if (!$filter || !$filter->status) {
      return [];
    }
    $allowed = array_keys($filter->settings['allowed_view_modes'] ?? []);
    $default = $filter->settings['default_view_mode'] ?? NULL;
    if ($default && !in_array($default, $allowed, TRUE)) {
      $allowed[] = $default;
    }
    return $this->forViewModes($allowed);
  }

  /**
   * Returns the shape and size of the given media view modes.
   *
   * @param array $view_modes
   *   The media view mode IDs.
   *
   * @return array
   *   An array of arrays keyed by view mode ID, with keys id, label, bundles,
   *   orientation, ratio, width, height, band, band_label and band_weight.
   *   View modes without an enabled display on any bundle are omitted. The
   *   default view mode, which has no entity_view_mode, is labelled Default.
   */
  public function forViewModes(array $view_modes): array {
    $labels = $this->entityTypeManager->getStorage('entity_view_mode')->loadMultiple();
    $displays = $this->entityTypeManager->getStorage('entity_view_display');
    $bundles = array_keys($this->entityTypeManager->getStorage('media_type')->loadMultiple());
    $ladder = $this->ladder();

    $out = [];
    foreach ($view_modes as $view_mode) {
      $mode = $labels['media.' . $view_mode] ?? NULL;
      if (!$mode && $view_mode !== 'default') {
        continue;
      }
      $found = [];
      $box = NULL;
      foreach ($bundles as $bundle) {
        $display = $displays->load('media.' . $bundle . '.' . $view_mode);
        if (!$display || !$display->status()) {
          continue;
        }
        $found[] = $bundle;
        $read = $this->readDisplay($display, $this->sourceField($bundle));
        if ($read && ($box === NULL || $bundle === 'image')) {
          $box = $read;
        }
      }
      if (!$found) {
        continue;
      }
      $entry = [
        'id' => $view_mode,
        'label' => $mode ? (string) $mode->label() : (string) $this->t('Default'),
        'bundles' => $found,
        'orientation' => NULL,
        'ratio' => NULL,
        'width' => NULL,
        'height' => NULL,
        'band' => NULL,
        'band_label' => NULL,
        'band_weight' => NULL,
      ];
      if ($box) {
        $entry['width'] = $box['width'];
        $entry['height'] = $box['height'];
        $entry['orientation'] = ImageStyleReader::orientationOf($box['width'], $box['height']);
        $entry['ratio'] = $box['height'] ? $box['ratio'] : NULL;
        $band = $this->band($box['width'], $box['group'] ?? NULL, $ladder);
        if ($band) {
          $entry['band'] = $band['id'];
          $entry['band_label'] = $band['label'];
          $entry['band_weight'] = $band['weight'];
        }
      }
      $out[$view_mode] = $entry;
    }
    return $out;
  }

  /**
   * Returns the size bands of each breakpoint group in use.
   *
   * @return array
   *   An array of arrays keyed by breakpoint group, each an array of bands
   *   with keys id, label, min and weight, narrowest first.
   */
  public function ladder(): array {
    $out = [];
    foreach ($this->groupsInUse() as $group) {
      $bands = [];
      foreach ($this->breakpointManager->getBreakpointsByGroup($group) as $breakpoint) {
        $min = self::minWidth($breakpoint->getMediaQuery());
        if ($min === NULL) {
          continue;
        }
        $bands[] = [
          'id' => 'min-' . $min,
          'label' => (string) $breakpoint->getLabel(),
          'min' => $min,
          'multipliers' => array_map(
            fn($m) => (float) rtrim((string) $m, 'x'),
            array_values($breakpoint->getMultipliers() ?: ['1x'])
          ),
        ];
      }
      if (!$bands) {
        continue;
      }
      usort($bands, fn($a, $b) => $a['min'] <=> $b['min']);
      $top = end($bands);
      $beyond = (int) round($top['min'] * max($top['multipliers'] ?: [1.0]));
      if ($beyond > $top['min']) {
        $bands[] = ['id' => 'beyond', 'label' => (string) $this->t('Wide'), 'min' => $beyond, 'multipliers' => [1.0]];
      }
      foreach ($bands as $weight => &$band) {
        $band['weight'] = $weight;
        unset($band['multipliers']);
      }
      $out[$group] = $bands;
    }
    return $out;
  }

  /**
   * Returns the minimum width of a media query.
   *
   * @param string $media_query
   *   The media query.
   *
   * @return int|null
   *   The minimum width in pixels, 0 if the query has no condition, or NULL
   *   if the query has no min-width condition.
   */
  public static function minWidth(string $media_query): ?int {
    if (preg_match('/min-width:\s*(\d+(?:\.\d+)?)px/i', $media_query, $matches)) {
      return (int) round((float) $matches[1]);
    }
    return trim($media_query) === '' || preg_match('/^all$/i', trim($media_query)) ? 0 : NULL;
  }

  /**
   * Returns the breakpoint groups used by responsive image styles.
   *
   * @return array
   *   An array of breakpoint group names, most used first.
   */
  protected function groupsInUse(): array {
    if (!$this->entityTypeManager->hasDefinition('responsive_image_style')) {
      return [];
    }
    $counts = [];
    foreach ($this->entityTypeManager->getStorage('responsive_image_style')->loadMultiple() as $style) {
      $group = $style->getBreakpointGroup();
      if ($group) {
        $counts[$group] = ($counts[$group] ?? 0) + 1;
      }
    }
    arsort($counts);
    return array_keys($counts);
  }

  /**
   * Returns the size band of a width.
   *
   * @param int|null $width
   *   The width in pixels, or NULL if none is set.
   * @param string|null $group
   *   The breakpoint group, or NULL for the first group in use.
   * @param array $ladder
   *   The size bands, as returned by ladder().
   *
   * @return array|null
   *   The widest band whose minimum the width reaches, or NULL if there is no
   *   width or no bands.
   */
  protected function band(?int $width, ?string $group, array $ladder): ?array {
    if (!$width || !$ladder) {
      return NULL;
    }
    $bands = $ladder[$group] ?? reset($ladder);
    $found = NULL;
    foreach ($bands as $band) {
      if ($width >= $band['min']) {
        $found = $band;
      }
    }
    return $found;
  }

  /**
   * Returns the dimensions of the source field on a view display.
   *
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The view display.
   * @param string|null $source_field
   *   The media source field name, or NULL if the bundle has none.
   *
   * @return array|null
   *   An array with keys width, height, ratio and group, or NULL if the
   *   display does not render the source field with an image style.
   */
  protected function readDisplay($display, ?string $source_field): ?array {
    $components = $display->getComponents();
    if ($source_field === NULL || !isset($components[$source_field])) {
      return NULL;
    }
    return $this->readComponent($components[$source_field]);
  }

  /**
   * Returns the dimensions produced by a display component.
   *
   * @param array $component
   *   The display component.
   *
   * @return array|null
   *   An array with keys width, height, ratio and group, or NULL if the
   *   component does not use an image style or responsive image style.
   */
  protected function readComponent(array $component): ?array {
    $settings = $component['settings'] ?? [];
    if (!empty($settings['responsive_image_style'])) {
      $read = $this->readResponsive((string) $settings['responsive_image_style']);
      if ($read) {
        return $read;
      }
    }
    if (!empty($settings['image_style'])) {
      $read = $this->reader->read((string) $settings['image_style']);
      if ($read && $read['width']) {
        return [
          'width' => $read['width'],
          'height' => $read['height'],
          'ratio' => $read['ratio'],
          'group' => NULL,
        ];
      }
    }
    return NULL;
  }

  /**
   * Returns the dimensions produced by a responsive image style.
   *
   * @param string $id
   *   The responsive image style ID.
   *
   * @return array|null
   *   An array with keys width, height, ratio and group, the height and ratio
   *   NULL when no mapped image style sets a height, or NULL if no mapped
   *   image style sets a width.
   */
  protected function readResponsive(string $id): ?array {
    if (!$this->entityTypeManager->hasDefinition('responsive_image_style')) {
      return NULL;
    }
    $responsive = $this->entityTypeManager->getStorage('responsive_image_style')->load($id);
    if (!$responsive) {
      return NULL;
    }
    $shape = NULL;
    $widest = NULL;
    foreach ($responsive->getImageStyleIds() as $style_id) {
      $read = $this->reader->read($style_id);
      if (!$read || !$read['width']) {
        continue;
      }
      if ($widest === NULL || $read['width'] > $widest['width']) {
        $widest = $read;
      }
      if ($shape === NULL && $read['height']) {
        $shape = $read;
      }
    }
    if ($widest === NULL) {
      return NULL;
    }
    $width = $this->layoutWidth($responsive) ?? $widest['width'];
    $height = NULL;
    if ($shape) {
      $height = (int) round($width * $shape['height'] / $shape['width'], 0, PHP_ROUND_HALF_EVEN);
    }
    return [
      'width' => $width,
      'height' => $height,
      'ratio' => $shape ? $shape['ratio'] : NULL,
      'group' => $responsive->getBreakpointGroup(),
    ];
  }

  /**
   * Returns the largest width in the sizes mappings of a responsive style.
   *
   * @param \Drupal\responsive_image\ResponsiveImageStyleInterface $responsive
   *   The responsive image style.
   *
   * @return int|null
   *   The width in pixels, or NULL if no sizes mapping sets one.
   */
  protected function layoutWidth($responsive): ?int {
    $widths = [];
    foreach ($responsive->getImageStyleMappings() as $mapping) {
      if (($mapping['image_mapping_type'] ?? NULL) !== 'sizes') {
        continue;
      }
      $sizes = $mapping['image_mapping']['sizes'] ?? '';
      $without_conditions = preg_replace('/\([^)]*\)/', '', (string) $sizes);
      if (preg_match_all('/(\d+(?:\.\d+)?)px/', $without_conditions, $matches)) {
        foreach ($matches[1] as $value) {
          $widths[] = (int) round((float) $value);
        }
      }
    }
    return $widths ? max($widths) : NULL;
  }

  /**
   * Returns the source field name of a media bundle.
   *
   * @param string $bundle
   *   The media bundle.
   *
   * @return string|null
   *   The source field name, or NULL if the bundle does not exist or has no
   *   source field.
   */
  protected function sourceField(string $bundle): ?string {
    if (!array_key_exists($bundle, $this->sourceFields)) {
      $type = $this->entityTypeManager->getStorage('media_type')->load($bundle);
      $field = $type ? ($type->getSource()->getConfiguration()['source_field'] ?? NULL) : NULL;
      $this->sourceFields[$bundle] = $field ?: NULL;
    }
    return $this->sourceFields[$bundle];
  }

}
