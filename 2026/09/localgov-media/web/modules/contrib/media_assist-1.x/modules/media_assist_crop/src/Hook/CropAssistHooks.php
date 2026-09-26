<?php

declare(strict_types=1);

namespace Drupal\media_assist_crop\Hook;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\crop\CropInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\media\MediaInterface;
use Drupal\media_assist_crop\Service\CropInfo;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Hook implementations for the media_assist_crop module.
 */
class CropAssistHooks {

  use StringTranslationTrait;

  /**
   * Constructs a CropAssistHooks.
   *
   * @param \Drupal\media_assist_crop\Service\CropInfo $info
   *   The crop info service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator.
   * @param \Drupal\file\FileUsage\FileUsageInterface $fileUsage
   *   The file usage service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    protected CropInfo $info,
    protected AccountProxyInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    protected FileUsageInterface $fileUsage,
    protected RequestStack $requestStack,
  ) {}

  /**
   * Implements hook_preprocess_HOOK() for image-formatter.html.twig.
   */
  #[Hook('preprocess_image_formatter')]
  public function preprocessImageFormatter(array &$variables): void {
    $variables['#cache']['contexts'][] = 'user.permissions';
    if (!$this->currentUser->hasPermission('view media assist labels')) {
      return;
    }
    $style_id = $variables['image_style'] ?? NULL;
    $read = $style_id ? $this->info->forStyle($style_id) : NULL;
    if ($read) {
      $this->label($variables['image'], $read, $style_id, 'image');
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for responsive-image.html.twig.
   */
  #[Hook('preprocess_responsive_image')]
  public function preprocessResponsiveImage(array &$variables): void {
    $variables['#cache']['contexts'][] = 'user.permissions';
    if (!$this->currentUser->hasPermission('view media assist labels')) {
      return;
    }
    $style_id = $variables['responsive_image_style_id'] ?? NULL;
    if (!$style_id || !isset($variables['img_element'])) {
      return;
    }
    $read = $this->responsiveRatio($style_id);
    if ($read) {
      $this->label($variables['img_element'], $read, $style_id, 'responsive');
    }
  }

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    $attachments['#cache']['contexts'][] = 'user.permissions';
    if (!$this->currentUser->hasPermission('view media assist labels')) {
      return;
    }
    $attachments['#attached']['library'][] = 'media_assist_crop/labels';
    $attachments['#attached']['drupalSettings']['mediaAssistCrop']['styleLabels'] = $this->info->styleLabels();
    $attachments['#cache']['tags'][] = 'config:image_style_list';
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    if (!preg_match('/^media_\w+_(edit|add)_form$/', $form_id)) {
      return;
    }
    if (!$this->currentUser->hasPermission('view media assist crop preview')) {
      return;
    }
    $form['#after_build'][] = [self::class, 'afterBuild'];
  }

  /**
   * After-build callback for media forms.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form.
   */
  public static function afterBuild(array $form, FormStateInterface $form_state): array {
    return \Drupal::service(self::class)->buildSummary($form, $form_state);
  }

  /**
   * Adds the crop summary element above the crop widget of a media form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form.
   */
  public function buildSummary(array $form, FormStateInterface $form_state): array {
    $object = $form_state->getFormObject();
    $media = method_exists($object, 'getEntity') ? $object->getEntity() : NULL;
    if (!$media instanceof MediaInterface || !$media->id()) {
      return $form;
    }
    $parents = $this->findCropElement($form);
    if (!$parents) {
      return $form;
    }

    $summary = $this->info->summaryFor($media, $this->offeredCropTypes($form_state, $parents[0] ?? ''));
    if (!$summary['total']) {
      return $form;
    }

    $arrival = NULL;
    $requested = $this->requestStack->getCurrentRequest()?->query->get('media_assist_crop');
    if ($requested) {
      $type = $this->entityTypeManager->getStorage('crop_type')->load($requested);
      if ($type) {
        $arrival = ['id' => $requested, 'label' => (string) $type->label()];
      }
    }

    $crop_element = NestedArray::getValue($form, $parents);
    $siblings = &NestedArray::getValue($form, array_slice($parents, 0, -1));
    $siblings['media_assist_crop_summary'] = [
      '#theme' => 'media_assist_crop_summary',
      '#set' => $summary['set'],
      '#unset' => $summary['unset'],
      '#set_count' => count($summary['set']),
      '#total' => $summary['total'],
      '#arrival' => $arrival,
      '#weight' => ($crop_element['#weight'] ?? 0) - 0.0001,
      '#attached' => ['library' => ['media_assist_crop/summary']],
    ];
    return $form;
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'media_assist_crop_preview' => [
        'variables' => [
          'tiles' => [],
          'set_count' => 0,
          'total' => 0,
          'edit_url' => NULL,
        ],
      ],
      'media_assist_crop_summary' => [
        'variables' => [
          'set' => [],
          'unset' => [],
          'set_count' => 0,
          'total' => 0,
          'arrival' => NULL,
        ],
      ],
    ];
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for crop entities.
   */
  #[Hook('crop_insert')]
  public function cropInsert(CropInterface $crop): void {
    $this->invalidate($crop);
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for crop entities.
   */
  #[Hook('crop_update')]
  public function cropUpdate(CropInterface $crop): void {
    $this->invalidate($crop);
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for crop entities.
   */
  #[Hook('crop_delete')]
  public function cropDelete(CropInterface $crop): void {
    $this->invalidate($crop);
  }

  /**
   * Flushes image styles and invalidates cache tags affected by a crop.
   *
   * @param \Drupal\crop\CropInterface $crop
   *   The crop entity.
   */
  protected function invalidate(CropInterface $crop): void {
    $uri = $crop->get('uri')->value;
    if (!$uri) {
      return;
    }
    $type = $crop->bundle();
    foreach ($this->entityTypeManager->getStorage('image_style')->loadMultiple() as $id => $style) {
      $read = $this->info->forStyle($id);
      if ($read && $read['crop_type'] === $type) {
        $style->flush($uri);
      }
    }

    $tags = [];
    $files = $this->entityTypeManager->getStorage('file')->loadByProperties(['uri' => $uri]);
    foreach ($files as $file) {
      $tags[] = 'file:' . $file->id();
      $usage = $this->fileUsage->listUsage($file);
      foreach (array_keys($usage['file']['media'] ?? []) as $id) {
        $tags[] = 'media:' . $id;
      }
    }
    if ($tags) {
      $this->cacheTagsInvalidator->invalidateTags(array_unique($tags));
    }
  }

  /**
   * Adds the crop label data attributes to an image element.
   *
   * @param array $element
   *   The image render element.
   * @param array $read
   *   The crop information, as returned by CropInfo::forStyle().
   * @param string $style_id
   *   The image style or responsive image style ID.
   * @param string $kind
   *   Either image or responsive.
   */
  protected function label(array &$element, array $read, string $style_id, string $kind): void {
    $element['#attributes']['data-media-assist-crop-ratio'] = $read['ratio'] ?? '';
    $element['#attributes']['data-media-assist-crop-type'] = $read['crop_type'];
    $element['#attributes']['data-media-assist-crop-style'] = $style_id;
    $element['#attributes']['data-media-assist-crop-kind'] = $kind;
  }

  /**
   * Returns the crop information of a responsive image style.
   *
   * @param string $style_id
   *   The responsive image style ID.
   *
   * @return array|null
   *   The crop information of the first mapped image style that crops, as
   *   returned by CropInfo::forStyle(), or NULL if none crops.
   */
  protected function responsiveRatio(string $style_id): ?array {
    $responsive = $this->entityTypeManager->getStorage('responsive_image_style')->load($style_id);
    if (!$responsive) {
      return NULL;
    }
    $candidates = [];
    foreach ($responsive->getImageStyleMappings() as $mapping) {
      $mapped = $mapping['image_mapping'] ?? NULL;
      if (is_array($mapped)) {
        $candidates = array_merge($candidates, array_values($mapped['sizes_image_styles'] ?? []));
      }
      elseif (is_string($mapped)) {
        $candidates[] = $mapped;
      }
    }
    $candidates[] = $responsive->getFallbackImageStyle();
    foreach (array_filter($candidates) as $candidate) {
      $read = $this->info->forStyle($candidate);
      if ($read) {
        return $read;
      }
    }
    return NULL;
  }

  /**
   * Returns the crop types offered by the crop widget of a form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $field
   *   The name of the field the crop widget belongs to.
   *
   * @return array|null
   *   An array of crop type IDs, or NULL if the widget does not limit them.
   */
  protected function offeredCropTypes(FormStateInterface $form_state, string $field): ?array {
    $object = $form_state->getFormObject();
    if (!$field || !method_exists($object, 'getFormDisplay')) {
      return NULL;
    }
    $component = $object->getFormDisplay($form_state)?->getComponent($field);
    $list = $component['settings']['crop_list'] ?? NULL;
    return is_array($list) && $list ? array_values($list) : NULL;
  }

  /**
   * Returns the parents of the first image_crop element in a form.
   *
   * @param array $form
   *   The form or form element.
   * @param array $parents
   *   The parents of the form element.
   *
   * @return array|null
   *   The parents of the image_crop element, or NULL if there is none.
   */
  protected function findCropElement(array $form, array $parents = []): ?array {
    foreach ($form as $key => $value) {
      if (!is_array($value) || (is_string($key) && str_starts_with($key, '#'))) {
        continue;
      }
      if (($value['#type'] ?? NULL) === 'image_crop') {
        return array_merge($parents, [$key]);
      }
      $found = $this->findCropElement($value, array_merge($parents, [$key]));
      if ($found) {
        return $found;
      }
    }
    return NULL;
  }

}
