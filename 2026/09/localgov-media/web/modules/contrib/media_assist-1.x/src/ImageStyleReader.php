<?php

declare(strict_types=1);

namespace Drupal\media_assist;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\image\ImageStyleInterface;

/**
 * Reads the crop type and dimensions produced by an image style.
 */
class ImageStyleReader {

  /**
   * The crop effect plugin IDs, keyed to the data key holding the crop type.
   */
  public const CROP_EFFECTS = [
    'crop_crop' => 'crop_type',
    'focal_point_crop' => 'crop_type',
    'focal_point_crop_by_width' => 'crop_type',
    'focal_point_scale_and_crop' => 'crop_type',
  ];

  /**
   * The effect plugin IDs that set a width or a height.
   */
  public const SIZE_EFFECTS = [
    'image_scale_and_crop',
    'image_resize',
    'image_scale',
    'focal_point_scale_and_crop',
    'focal_point_crop',
    'focal_point_crop_by_width',
  ];

  /**
   * The named aspect ratios, widest first.
   */
  public const NAMED_RATIOS = ['21:9', '2:1', '16:9', '3:2', '4:3', '5:4', '1:1', '4:5', '3:4', '2:3', '9:16'];

  /**
   * The relative tolerance within which dimensions snap to a named ratio.
   */
  public const SNAP_TOLERANCE = 0.02;

  /**
   * The results of read(), keyed by image style ID.
   *
   * @var array
   */
  protected array $cache = [];

  /**
   * The aspect ratios of the site's crop types, or NULL if not yet loaded.
   *
   * @var array|null
   */
  protected ?array $cropTypeRatios = NULL;

  /**
   * Constructs an ImageStyleReader.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * Reads the crop type and dimensions produced by an image style.
   *
   * @param string $style_id
   *   The image style ID.
   *
   * @return array|null
   *   An array with keys crop_type, width, height and ratio, each NULL when
   *   the style does not set it, or NULL if the style does not exist. The
   *   ratio is the aspect ratio of the style's crop type when the dimensions
   *   are within SNAP_TOLERANCE of it or are not both set; otherwise the
   *   nearest aspect ratio of any crop type or in NAMED_RATIOS within that
   *   tolerance; otherwise the dimensions in their lowest terms.
   */
  public function read(string $style_id): ?array {
    if (array_key_exists($style_id, $this->cache)) {
      return $this->cache[$style_id];
    }
    $style = $this->entityTypeManager->getStorage('image_style')->load($style_id);
    $this->cache[$style_id] = $style ? $this->effects($style) : NULL;
    return $this->cache[$style_id];
  }

  /**
   * Returns the aspect ratio of the given dimensions.
   *
   * @param int $width
   *   The width in pixels.
   * @param int $height
   *   The height in pixels.
   * @param string[] $named
   *   The named ratios to snap to, as W:H strings.
   *
   * @return string
   *   The nearest named ratio within SNAP_TOLERANCE, or the dimensions in
   *   their lowest terms.
   */
  public static function ratioOf(int $width, int $height, array $named = self::NAMED_RATIOS): string {
    if ($snapped = self::snap($width / $height, $named)) {
      return $snapped;
    }
    $divisor = self::gcd($width, $height);
    return ($width / $divisor) . ':' . ($height / $divisor);
  }

  /**
   * Returns the orientation of the given dimensions.
   *
   * @param int|null $width
   *   The width in pixels, or NULL if none is set.
   * @param int|null $height
   *   The height in pixels, or NULL if none is set.
   *
   * @return string|null
   *   One of landscape, portrait, square or freestyle, or NULL if there is no
   *   width.
   */
  public static function orientationOf(?int $width, ?int $height): ?string {
    if (!$width) {
      return NULL;
    }
    if (!$height) {
      return 'freestyle';
    }
    return $width <=> $height ? ($width > $height ? 'landscape' : 'portrait') : 'square';
  }

  /**
   * Returns the image styles used by view displays and responsive styles.
   *
   * @return array
   *   An array of TRUE values keyed by image style ID.
   */
  public function stylesInUse(): array {
    $ids = [];
    if ($this->entityTypeManager->hasDefinition('responsive_image_style')) {
      foreach ($this->entityTypeManager->getStorage('responsive_image_style')->loadMultiple() as $responsive) {
        foreach ($responsive->getImageStyleIds() as $id) {
          $ids[$id] = TRUE;
        }
      }
    }
    foreach ($this->entityTypeManager->getStorage('entity_view_display')->loadMultiple() as $display) {
      foreach ($display->getComponents() as $component) {
        $style = $component['settings']['image_style'] ?? NULL;
        if (is_string($style) && $style !== '') {
          $ids[$style] = TRUE;
        }
      }
    }
    return $ids;
  }

  /**
   * Returns the aspect ratios of the site's crop types.
   *
   * @return string[]
   *   The aspect ratios as W:H strings, keyed by crop type ID, for the crop
   *   types that have one.
   */
  public function cropTypeRatios(): array {
    if ($this->cropTypeRatios === NULL) {
      $this->cropTypeRatios = [];
      if ($this->entityTypeManager->hasDefinition('crop_type')) {
        foreach ($this->entityTypeManager->getStorage('crop_type')->loadMultiple() as $id => $type) {
          $ratio = $type->get('aspect_ratio');
          if (is_string($ratio) && preg_match('/^([1-9]\d*):([1-9]\d*)$/', $ratio)) {
            $this->cropTypeRatios[$id] = $ratio;
          }
        }
      }
    }
    return $this->cropTypeRatios;
  }

  /**
   * Reads the effects of an image style.
   *
   * @param \Drupal\image\ImageStyleInterface $style
   *   The image style.
   *
   * @return array
   *   An array with keys crop_type, width, height and ratio.
   */
  protected function effects(ImageStyleInterface $style): array {
    $crop_type = NULL;
    $width = NULL;
    $height = NULL;
    foreach ($style->getEffects() as $effect) {
      $id = $effect->getPluginId();
      $data = $effect->getConfiguration()['data'] ?? [];
      if (isset(self::CROP_EFFECTS[$id])) {
        $crop_type = $data[self::CROP_EFFECTS[$id]] ?? $crop_type;
      }
      if (in_array($id, self::SIZE_EFFECTS, TRUE)) {
        $width = !empty($data['width']) ? (int) $data['width'] : $width;
        $height = !empty($data['height']) ? (int) $data['height'] : $height;
      }
    }
    return [
      'crop_type' => $crop_type,
      'width' => $width,
      'height' => $height,
      'ratio' => $this->ratio($crop_type, $width, $height),
    ];
  }

  /**
   * Returns the aspect ratio of a crop type and the dimensions it is used at.
   *
   * @param string|null $crop_type
   *   The crop type ID, or NULL if the style has no crop effect.
   * @param int|null $width
   *   The width in pixels, or NULL if none is set.
   * @param int|null $height
   *   The height in pixels, or NULL if none is set.
   *
   * @return string|null
   *   The ratio as described for read(), or NULL if there is none.
   */
  protected function ratio(?string $crop_type, ?int $width, ?int $height): ?string {
    $ratios = $this->cropTypeRatios();
    $own = $crop_type ? ($ratios[$crop_type] ?? NULL) : NULL;
    if (!$width || !$height) {
      return $own;
    }
    if ($own && self::snap($width / $height, [$own])) {
      return $own;
    }
    return self::ratioOf($width, $height, array_merge(array_values($ratios), self::NAMED_RATIOS));
  }

  /**
   * Returns the named ratio nearest to a value.
   *
   * @param float $actual
   *   The width divided by the height.
   * @param string[] $named
   *   The named ratios, as W:H strings.
   *
   * @return string|null
   *   The nearest named ratio within SNAP_TOLERANCE of the value, or NULL if
   *   none is that close.
   */
  protected static function snap(float $actual, array $named): ?string {
    $nearest = NULL;
    $nearest_distance = self::SNAP_TOLERANCE;
    foreach ($named as $name) {
      [$w, $h] = array_map('intval', explode(':', $name));
      if (!$w || !$h) {
        continue;
      }
      $distance = abs($actual - $w / $h) / ($w / $h);
      if ($distance < $nearest_distance) {
        $nearest = $name;
        $nearest_distance = $distance;
      }
    }
    return $nearest;
  }

  /**
   * Returns the greatest common divisor of two integers.
   *
   * @param int $a
   *   The first integer.
   * @param int $b
   *   The second integer.
   *
   * @return int
   *   The greatest common divisor, at least 1.
   */
  protected static function gcd(int $a, int $b): int {
    return $b === 0 ? max($a, 1) : self::gcd($b, $a % $b);
  }

}
