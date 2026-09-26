<?php

declare(strict_types=1);

namespace Drupal\Tests\media_assist_embed\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\image\Entity\ImageStyle;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the view mode shapes service.
 *
 * @group media_assist_embed
 */
#[Group('media_assist_embed')]
#[RunTestsInSeparateProcesses]
class ViewModeShapesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'image',
    'media',
    'breakpoint',
    'responsive_image',
    'filter',
    'media_assist',
    'media_assist_embed',
    'media_assist_embed_test',
  ];

  /**
   * The view mode shapes service.
   *
   * @var \Drupal\media_assist_embed\Service\ViewModeShapes
   */
  protected $shapes;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installConfig(['system', 'field', 'image', 'media']);
    $this->shapes = $this->container->get('media_assist_embed.view_mode_shapes');
    $this->createMediaType('image');
  }

  /**
   * Tests the orientation and aspect ratio of view modes.
   */
  public function testOrientation(): void {
    $this->style('mea_wide', 768, 432);
    $this->style('mea_tall', 768, 1024);
    $this->style('mea_box', 768, 768);
    $this->style('mea_free', 768, NULL);

    $this->viewMode('wide_mode', 'Wide one', 'mea_wide');
    $this->viewMode('tall_mode', 'Tall one', 'mea_tall');
    $this->viewMode('box_mode', 'Box one', 'mea_box');
    $this->viewMode('free_mode', 'Free one', 'mea_free');

    $read = $this->shapes->forViewModes(['wide_mode', 'tall_mode', 'box_mode', 'free_mode']);
    $this->assertSame('landscape', $read['wide_mode']['orientation']);
    $this->assertSame('16:9', $read['wide_mode']['ratio']);
    $this->assertSame('portrait', $read['tall_mode']['orientation']);
    $this->assertSame('3:4', $read['tall_mode']['ratio']);
    $this->assertSame('square', $read['box_mode']['orientation']);
    $this->assertSame('1:1', $read['box_mode']['ratio']);
    $this->assertSame('freestyle', $read['free_mode']['orientation']);
    $this->assertNull($read['free_mode']['ratio']);
  }

  /**
   * Tests that size bands are built from the breakpoint group in use.
   */
  public function testBandsFromBreakpointGroup(): void {
    $this->style('mea_a', 320, 180);
    $this->responsive('r_small', 'mea_a', '(min-width: 768px) 320px, 100vw');
    $this->viewMode('small', 'Small one', NULL, 'r_small');

    $ladder = $this->shapes->ladder();
    $this->assertArrayHasKey('media_assist_embed_test', $ladder);
    $this->assertSame(
      ['Phone', 'Tablet', 'Desk', 'Wide'],
      array_column($ladder['media_assist_embed_test'], 'label')
    );
    $this->assertSame(
      [0, 700, 1000, 2000],
      array_column($ladder['media_assist_embed_test'], 'min'),
      'The last is the widest breakpoint at its highest multiplier'
    );
  }

  /**
   * Tests that the width is taken from the sizes attribute.
   */
  public function testSizeFromLayoutWidth(): void {
    $this->style('mea_s', 320, 180);
    $this->style('mea_m', 768, 432);
    $this->style('mea_l', 1200, 675);
    $candidates = ['mea_s', 'mea_m', 'mea_l'];
    $this->responsive('r_small', $candidates, '(min-width: 700px) 320px, 100vw');
    $this->responsive('r_large', $candidates, '(min-width: 1000px) 1200px, 100vw');
    $this->viewMode('small', 'Small', NULL, 'r_small');
    $this->viewMode('large', 'Large', NULL, 'r_large');

    $read = $this->shapes->forViewModes(['small', 'large']);
    $this->assertSame(320, $read['small']['width']);
    $this->assertSame(1200, $read['large']['width']);
    $this->assertSame('Phone', $read['small']['band_label']);
    $this->assertSame('Desk', $read['large']['band_label']);
    $this->assertSame('16:9', $read['small']['ratio']);
    $this->assertSame(180, $read['small']['height']);
    $this->assertSame('16:9', $read['large']['ratio']);
    $this->assertSame(675, $read['large']['height']);
  }

  /**
   * Tests that media condition widths in a sizes attribute are ignored.
   */
  public function testMediaConditionWidthsIgnored(): void {
    $this->style('mea_s', 320, 180);
    $this->responsive('r', ['mea_s'], '(min-width: 700px) 320px, 100vw');
    $this->viewMode('small', 'Small', NULL, 'r');

    $read = $this->shapes->forViewModes(['small']);
    $this->assertSame(320, $read['small']['width']);
  }

  /**
   * Tests that widths beyond the widest breakpoint are banded.
   */
  public function testWidthsBeyondBreakpoints(): void {
    $this->style('mea_huge', 2560, 1440);
    $this->responsive('r_huge', ['mea_huge'], '(min-width: 1000px) 2560px, 100vw');
    $this->viewMode('huge_mode', 'Huge', NULL, 'r_huge');
    $this->style('mea_big', 1200, 675);
    $this->responsive('r_big', ['mea_big'], '(min-width: 1000px) 1200px, 100vw');
    $this->viewMode('big_mode', 'Big', NULL, 'r_big');

    $read = $this->shapes->forViewModes(['huge_mode', 'big_mode']);
    $this->assertSame('Wide', $read['huge_mode']['band_label'], '2560 is past 1000 at 2x');
    $this->assertSame('Desk', $read['big_mode']['band_label']);
  }

  /**
   * Tests that a plain image style is banded by its width.
   */
  public function testPlainImageStyleBands(): void {
    $this->style('mea_plain', 384, 216);
    $this->style('mea_m', 768, 432);
    $this->responsive('r', ['mea_m'], '(min-width: 700px) 768px, 100vw');
    $this->viewMode('teaser', 'Teaser', 'mea_plain');
    $this->viewMode('medium', 'Medium', NULL, 'r');

    $read = $this->shapes->forViewModes(['teaser', 'medium']);
    $this->assertSame('Phone', $read['teaser']['band_label']);
    $this->assertSame(384, $read['teaser']['width']);
    $this->assertSame('Tablet', $read['medium']['band_label']);
  }

  /**
   * Tests view modes without a display or without an image.
   */
  public function testViewModesWithoutImages(): void {
    EntityViewMode::create([
      'id' => 'media.orphan',
      'targetEntityType' => 'media',
      'label' => 'Orphan',
    ])->save();
    $this->viewMode('bare', 'Bare', NULL);

    $read = $this->shapes->forViewModes(['orphan', 'bare', 'never_created']);
    $this->assertArrayNotHasKey('orphan', $read);
    $this->assertArrayNotHasKey('never_created', $read);
    $this->assertArrayHasKey('bare', $read);
    $this->assertNull($read['bare']['orientation']);
    $this->assertNull($read['bare']['band']);
  }

  /**
   * Tests that the bundles with a display are reported.
   */
  public function testBundlesAreReported(): void {
    $this->createMediaType('document');
    $this->style('mea_wide', 768, 432);
    $this->viewMode('shared', 'Shared', 'mea_wide', NULL, ['image', 'document']);
    $this->viewMode('image_only', 'Image only', 'mea_wide', NULL, ['image']);

    $read = $this->shapes->forViewModes(['shared', 'image_only']);
    $this->assertSame(['document', 'image'], $read['shared']['bundles']);
    $this->assertSame(['image'], $read['image_only']['bundles']);
  }

  /**
   * Tests that only the view modes allowed by a text format are returned.
   */
  public function testAllowedViewModes(): void {
    $this->style('mea_wide', 768, 432);
    $this->viewMode('default', 'Default', 'mea_wide');
    $this->viewMode('wide_mode', 'Wide', 'mea_wide');
    $this->viewMode('unlisted', 'Unlisted', 'mea_wide');

    $format = \Drupal::entityTypeManager()->getStorage('filter_format')->create([
      'format' => 'test',
      'name' => 'Test',
      'filters' => [
        'media_embed' => [
          'id' => 'media_embed',
          'status' => TRUE,
          'settings' => [
            'default_view_mode' => 'wide_mode',
            'allowed_view_modes' => ['default' => 'default', 'wide_mode' => 'wide_mode'],
          ],
        ],
      ],
    ]);
    $format->save();

    $read = $this->shapes->forFormat($format);
    $this->assertSame(['default', 'wide_mode'], array_keys($read));
    $this->assertSame('Default', $read['default']['label']);
    $this->assertSame('16:9', $read['default']['ratio']);
  }

  /**
   * Creates a media type with the image source, if it does not exist.
   *
   * @param string $id
   *   The media type ID.
   */
  protected function createMediaType(string $id): void {
    $storage = \Drupal::entityTypeManager()->getStorage('media_type');
    if ($storage->load($id)) {
      return;
    }
    $type = $storage->create([
      'id' => $id,
      'label' => ucfirst($id),
      'source' => 'image',
    ]);
    $type->save();
    $source = $type->getSource();
    $field = $source->createSourceField($type);
    $field->getFieldStorageDefinition()->save();
    $field->save();
    $type->set('source_configuration', ['source_field' => $field->getName()])->save();
  }

  /**
   * Creates an image style, if it does not exist.
   *
   * @param string $id
   *   The image style ID.
   * @param int $width
   *   The width in pixels.
   * @param int|null $height
   *   The height in pixels, or NULL to scale to the width only.
   */
  protected function style(string $id, int $width, ?int $height): void {
    if (ImageStyle::load($id)) {
      return;
    }
    $style = ImageStyle::create(['name' => $id, 'label' => 'Style ' . $id]);
    $style->addImageEffect([
      'id' => $height ? 'image_scale_and_crop' : 'image_scale',
      'weight' => 1,
      'data' => ['width' => $width, 'height' => $height],
    ]);
    $style->save();
  }

  /**
   * Creates a responsive image style, if it does not exist.
   *
   * @param string $id
   *   The responsive image style ID.
   * @param string|string[] $candidates
   *   The image style ID or IDs of the sizes mapping. The first is also the
   *   fallback image style.
   * @param string $sizes
   *   The sizes attribute.
   */
  protected function responsive(string $id, $candidates, string $sizes): void {
    $candidates = (array) $candidates;
    if (ResponsiveImageStyle::load($id)) {
      return;
    }
    $style = ResponsiveImageStyle::create([
      'id' => $id,
      'label' => 'Responsive ' . $id,
      'breakpoint_group' => 'media_assist_embed_test',
      'fallback_image_style' => reset($candidates),
    ]);
    $style->addImageStyleMapping('media_assist_embed_test.phone', '1x', [
      'image_mapping_type' => 'sizes',
      'image_mapping' => ['sizes' => $sizes, 'sizes_image_styles' => $candidates],
    ]);
    $style->save();
  }

  /**
   * Creates a media view mode and a view display on each of the given bundles.
   *
   * @param string $id
   *   The view mode ID, without the entity type prefix. No view mode entity is
   *   created for default.
   * @param string $label
   *   The view mode label.
   * @param string|null $image_style
   *   The image style ID, or NULL for none.
   * @param string|null $responsive
   *   The responsive image style ID, which takes precedence over
   *   $image_style, or NULL for none.
   * @param string[] $bundles
   *   The media bundles.
   */
  protected function viewMode(string $id, string $label, ?string $image_style, ?string $responsive = NULL, array $bundles = ['image']): void {
    if ($id !== 'default' && !EntityViewMode::load('media.' . $id)) {
      EntityViewMode::create([
        'id' => 'media.' . $id,
        'targetEntityType' => 'media',
        'label' => $label,
      ])->save();
    }
    foreach ($bundles as $bundle) {
      $existing = \Drupal::entityTypeManager()->getStorage('entity_view_display')
        ->load('media.' . $bundle . '.' . $id);
      if ($existing) {
        $existing->delete();
      }
      $display = EntityViewDisplay::create([
        'targetEntityType' => 'media',
        'bundle' => $bundle,
        'mode' => $id,
        'status' => TRUE,
      ]);
      if ($image_style || $responsive) {
        $display->setComponent('field_media_' . $bundle, $responsive
          ? ['type' => 'responsive_image', 'settings' => ['responsive_image_style' => $responsive]]
          : ['type' => 'image', 'settings' => ['image_style' => $image_style]]
        );
      }
      $display->save();
    }
  }

}
