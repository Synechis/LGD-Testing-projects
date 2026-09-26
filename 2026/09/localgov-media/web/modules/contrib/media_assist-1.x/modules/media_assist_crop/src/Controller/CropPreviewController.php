<?php

declare(strict_types=1);

namespace Drupal\media_assist_crop\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\media\MediaInterface;
use Drupal\media_assist_crop\Service\CropInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for the media crop preview routes.
 */
class CropPreviewController extends ControllerBase {

  /**
   * The preview tile width in pixels.
   */
  const TILE_WIDTH = 320;

  /**
   * Constructs a CropPreviewController.
   *
   * @param \Drupal\media_assist_crop\Service\CropInfo $info
   *   The crop info service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    protected CropInfo $info,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('media_assist_crop.crop_info'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Returns the title of the crop preview page.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media item.
   *
   * @return string
   *   The page title.
   */
  public function title(MediaInterface $media): string {
    return (string) $this->t('Crop preview: @name', ['@name' => $media->label()]);
  }

  /**
   * Builds the crop preview page.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media item.
   *
   * @return array
   *   A render array.
   */
  public function page(MediaInterface $media): array {
    $file = $this->info->sourceFile($media);
    if (!$file) {
      return [
        '#markup' => '<p>' . $this->t('This media item has no image to crop.') . '</p>',
        '#cache' => ['tags' => $media->getCacheTags()],
      ];
    }

    $uri = $file->getFileUri();
    $crop_storage = $this->entityTypeManager()->getStorage('crop');
    $style_storage = $this->entityTypeManager()->getStorage('image_style');

    $set_cache = [];
    $tiles = [];
    foreach ($this->info->ratios(self::TILE_WIDTH) as $entry) {
      $style = $style_storage->load($entry['style']);
      if (!$style) {
        continue;
      }
      $crop_type = $entry['crop_type'];
      if (!isset($set_cache[$crop_type])) {
        $set_cache[$crop_type] = (bool) $crop_storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('type', $crop_type)
          ->condition('uri', $uri)
          ->count()
          ->execute();
      }
      $tiles[] = [
        'crop_type' => $crop_type,
        'label' => $entry['label'],
        'ratio' => $entry['ratio'],
        'set' => $set_cache[$crop_type],
        'url' => $style->buildUrl($uri),
        'width' => $entry['width'],
        'height' => $entry['height'],
        'style' => $entry['style'],
        'edit_url' => Url::fromRoute('entity.media.edit_form', ['media' => $media->id()], [
          'query' => ['media_assist_crop' => $crop_type],
        ])->toString(),
      ];
    }

    usort($tiles, fn(array $a, array $b) => $a['set'] <=> $b['set']);

    return [
      '#theme' => 'media_assist_crop_preview',
      '#tiles' => $tiles,
      '#set_count' => count(array_filter($set_cache)),
      '#total' => count($set_cache),
      '#edit_url' => Url::fromRoute('entity.media.edit_form', ['media' => $media->id()])->toString(),
      '#attached' => ['library' => ['media_assist_crop/preview']],
      '#cache' => [
        'tags' => array_merge($media->getCacheTags(), ['crop_list', 'image_style_list', 'config:crop_type_list']),
        'contexts' => ['user.permissions'],
      ],
    ];
  }

}
