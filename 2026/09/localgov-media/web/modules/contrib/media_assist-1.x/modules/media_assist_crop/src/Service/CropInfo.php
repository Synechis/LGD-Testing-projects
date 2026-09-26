<?php

declare(strict_types=1);

namespace Drupal\media_assist_crop\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media_assist\ImageStyleReader;

/**
 * Provides crop information about image styles and media items.
 */
class CropInfo {

  /**
   * The image styles in use, or NULL if not yet loaded.
   *
   * @var array|null
   */
  protected ?array $inUse = NULL;

  /**
   * Constructs a CropInfo.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\media_assist\ImageStyleReader $reader
   *   The image style reader.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ImageStyleReader $reader,
  ) {}

  /**
   * Returns the crop information of an image style.
   *
   * @param string $style_id
   *   The image style ID.
   *
   * @return array|null
   *   An array with keys crop_type, crop_label, width, height and ratio, or
   *   NULL if the style does not crop or its crop type does not exist.
   */
  public function forStyle(string $style_id): ?array {
    $read = $this->reader->read($style_id);
    if (!$read || $read['crop_type'] === NULL) {
      return NULL;
    }
    $type = $this->entityTypeManager->getStorage('crop_type')->load($read['crop_type']);
    if (!$type) {
      return NULL;
    }
    return [
      'crop_type' => $read['crop_type'],
      'crop_label' => (string) $type->label(),
      'width' => $read['width'],
      'height' => $read['height'],
      'ratio' => $read['ratio'],
    ];
  }

  /**
   * Returns the distinct aspect ratios cropped to by image styles in use.
   *
   * @param int $target
   *   The preferred width in pixels, used to choose the style for each ratio.
   *
   * @return array
   *   An array of arrays with keys ratio, label, crop_type, crop_label, style,
   *   width and height, one per crop type and ratio, widest first.
   */
  public function ratios(int $target = 768): array {
    $best = [];
    $in_use = $this->stylesInUse();
    foreach (array_keys($this->entityTypeManager->getStorage('image_style')->loadMultiple()) as $id) {
      if ($in_use && !isset($in_use[$id])) {
        continue;
      }
      $read = $this->forStyle($id);
      if (!$read || !$read['width']) {
        continue;
      }
      $key = $read['crop_type'] . '|' . ($read['ratio'] ?? '');
      $distance = abs($read['width'] - $target);
      if (!isset($best[$key]) || $distance < $best[$key]['distance']) {
        $best[$key] = [
          'ratio' => $read['ratio'],
          'label' => $read['ratio'] ?? $read['crop_label'],
          'crop_type' => $read['crop_type'],
          'crop_label' => $read['crop_label'],
          'style' => $id,
          'width' => $read['width'],
          'height' => $read['height'],
          'distance' => $distance,
        ];
      }
    }
    uasort($best, function (array $a, array $b) {
      $wide = fn(array $e) => $e['height'] ? $e['width'] / $e['height'] : -1;
      return [$wide($b), $a['label']] <=> [$wide($a), $b['label']];
    });
    foreach ($best as &$entry) {
      unset($entry['distance']);
    }
    return array_values($best);
  }

  /**
   * Returns the image styles used by view displays and responsive styles.
   *
   * @return array
   *   An array of TRUE values keyed by image style ID.
   */
  public function stylesInUse(): array {
    if ($this->inUse === NULL) {
      $this->inUse = $this->reader->stylesInUse();
    }
    return $this->inUse;
  }

  /**
   * Returns the crop types used by image styles in use.
   *
   * @return array
   *   An array of arrays with keys id, label and styles, the last being the
   *   IDs of the image styles using the crop type.
   */
  public function usedCropTypes(): array {
    $styles_by_type = [];
    $in_use = $this->stylesInUse();
    foreach (array_keys($this->entityTypeManager->getStorage('image_style')->loadMultiple()) as $id) {
      if ($in_use && !isset($in_use[$id])) {
        continue;
      }
      $read = $this->forStyle($id);
      if ($read) {
        $styles_by_type[$read['crop_type']][] = $id;
      }
    }
    $out = [];
    foreach ($this->entityTypeManager->getStorage('crop_type')->loadMultiple() as $id => $type) {
      if (isset($styles_by_type[$id])) {
        $out[] = [
          'id' => $id,
          'label' => (string) $type->label(),
          'styles' => $styles_by_type[$id],
        ];
      }
    }
    return $out;
  }

  /**
   * Returns the labels of the image styles that crop.
   *
   * @return array
   *   An array of arrays with keys short and full, keyed by image style ID.
   */
  public function styleLabels(): array {
    $labels = [];
    foreach ($this->entityTypeManager->getStorage('image_style')->loadMultiple() as $id => $style) {
      $read = $this->forStyle($id);
      if (!$read) {
        continue;
      }
      $box = $read['width'] && $read['height']
        ? $read['width'] . '×' . $read['height']
        : ($read['width'] ? $read['width'] . 'px wide' : '');
      $short = trim(($read['ratio'] ?? $read['crop_label']) . ' ' . $box);
      $labels[$id] = [
        'short' => $short !== '' ? $short : (string) $style->label(),
        'full' => (string) $style->label(),
      ];
    }
    return $labels;
  }

  /**
   * Returns the set and unset crop types of a media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media item.
   * @param array|null $only
   *   The crop type IDs to include, or NULL for all crop types in use.
   *
   * @return array
   *   An array with keys set and unset, each an array of arrays with keys id
   *   and label, and key total, the number of crop types counted.
   */
  public function summaryFor(MediaInterface $media, ?array $only = NULL): array {
    $file = $this->sourceFile($media);
    if (!$file) {
      return ['set' => [], 'unset' => [], 'total' => 0];
    }
    $uri = $file->getFileUri();
    $crop_storage = $this->entityTypeManager->getStorage('crop');
    $set = [];
    $unset = [];
    foreach ($this->usedCropTypes() as $entry) {
      if ($only !== NULL && !in_array($entry['id'], $only, TRUE)) {
        continue;
      }
      $has = (bool) $crop_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', $entry['id'])
        ->condition('uri', $uri)
        ->count()
        ->execute();
      $item = ['id' => $entry['id'], 'label' => $entry['label']];
      if ($has) {
        $set[] = $item;
      }
      else {
        $unset[] = $item;
      }
    }
    return ['set' => $set, 'unset' => $unset, 'total' => count($set) + count($unset)];
  }

  /**
   * Returns the source image file of a media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media item.
   *
   * @return \Drupal\file\FileInterface|null
   *   The file, or NULL if the media item has no image source file.
   */
  public function sourceFile(MediaInterface $media): ?FileInterface {
    $source = $media->getSource();
    if ($source->getPluginId() !== 'image') {
      return NULL;
    }
    $field = $source->getConfiguration()['source_field'] ?? NULL;
    if (!$field || !$media->hasField($field) || $media->get($field)->isEmpty()) {
      return NULL;
    }
    $file = $media->get($field)->entity;
    return $file instanceof FileInterface ? $file : NULL;
  }

}
