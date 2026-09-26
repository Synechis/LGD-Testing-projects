<?php

declare(strict_types=1);

namespace Drupal\Tests\media_assist_crop\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\crop\Entity\CropType;
use Drupal\image\Entity\ImageStyle;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the crop info service.
 *
 * @group media_assist_crop
 */
#[Group('media_assist_crop')]
#[RunTestsInSeparateProcesses]
class CropInfoTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'file', 'image', 'crop', 'media_assist', 'media_assist_crop'];

  /**
   * The crop info service.
   *
   * @var \Drupal\media_assist_crop\Service\CropInfo
   */
  protected $info;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('crop');
    $this->info = $this->container->get('media_assist_crop.crop_info');
  }

  /**
   * Creates a crop type.
   *
   * @param string $id
   *   The crop type ID.
   * @param string $label
   *   The crop type label.
   * @param string|null $ratio
   *   The aspect ratio, or NULL for none.
   */
  protected function cropType(string $id, string $label, ?string $ratio = NULL): void {
    CropType::create(['id' => $id, 'label' => $label, 'aspect_ratio' => $ratio])->save();
  }

  /**
   * Creates an image style with a scale and crop effect.
   *
   * @param string $id
   *   The image style ID.
   * @param string|null $crop_type
   *   The crop type of a preceding crop effect, or NULL for no crop effect.
   * @param int $width
   *   The width in pixels.
   * @param int $height
   *   The height in pixels.
   */
  protected function style(string $id, ?string $crop_type, int $width, int $height): void {
    $style = ImageStyle::create(['name' => $id, 'label' => 'Style ' . $id]);
    if ($crop_type) {
      $style->addImageEffect([
        'id' => 'crop_crop',
        'weight' => 1,
        'data' => ['crop_type' => $crop_type],
      ]);
    }
    $style->addImageEffect([
      'id' => 'image_scale_and_crop',
      'weight' => 2,
      'data' => ['width' => $width, 'height' => $height],
    ]);
    $style->save();
  }

  /**
   * Tests that the crop type and dimensions are read from an image style.
   */
  public function testReadsCropAndBox(): void {
    $this->cropType('16_9', '16:9', '16:9');
    $this->style('wide_768', '16_9', 768, 432);
    $this->style('no_crop', NULL, 100, 100);

    $read = $this->info->forStyle('wide_768');
    $this->assertSame('16_9', $read['crop_type']);
    $this->assertSame('16:9', $read['crop_label']);
    $this->assertSame(768, $read['width']);
    $this->assertSame(432, $read['height']);

    $this->assertNull($this->info->forStyle('no_crop'));
    $this->assertNull($this->info->forStyle('does_not_exist'));
  }

  /**
   * Tests that only crop types used by an image style are returned.
   */
  public function testCropTypesInUse(): void {
    $this->cropType('16_9', '16:9', '16:9');
    $this->cropType('square', 'Square', '1:1');
    $this->cropType('orphan', 'Orphan', '1:1');
    $this->style('wide', '16_9', 768, 432);
    $this->style('sq', 'square', 400, 400);

    $used = $this->info->usedCropTypes();
    $this->assertSame(['16_9', 'square'], array_column($used, 'id'));
    $this->assertSame(['16:9', 'Square'], array_column($used, 'label'));
  }

  /**
   * Tests that crop types with the same aspect ratio are kept separate.
   */
  public function testCropTypesKeptSeparate(): void {
    $this->cropType('1_1', '1:1', '1:1');
    $this->cropType('square', 'Square', '1:1');
    $this->style('a', '1_1', 400, 400);
    $this->style('b', 'square', 300, 300);

    $this->assertCount(2, $this->info->usedCropTypes());
  }

  /**
   * Tests that a crop type without an aspect ratio is returned.
   */
  public function testCropTypeWithNoRatio(): void {
    $this->cropType('freestyle', 'Freestyle', NULL);
    $this->style('free', 'freestyle', 500, 500);

    $used = $this->info->usedCropTypes();
    $this->assertSame(['freestyle'], array_column($used, 'id'));
    $this->assertSame(['free'], $used[0]['styles']);
  }

  /**
   * Tests the image style labels.
   */
  public function testStyleLabels(): void {
    $this->cropType('16_9', '16:9', '16:9');
    $this->style('wide', '16_9', 768, 432);
    $this->style('plain', NULL, 100, 100);

    $this->assertSame(
      ['wide' => ['short' => '16:9 768×432', 'full' => 'Style wide']],
      $this->info->styleLabels()
    );
  }

  /**
   * Tests the aspect ratios with one crop type per ratio.
   */
  public function testRatiosUnderManualCrop(): void {
    foreach ([['21_9', '21:9'], ['16_9', '16:9'], ['4_3', '4:3'], ['1_1', '1:1']] as [$id, $label]) {
      $this->cropType($id, $label, $label);
    }
    $this->cropType('freestyle', 'Freestyle', NULL);
    $this->style('a', '21_9', 768, 329);
    $this->style('b', '16_9', 768, 432);
    $this->style('c', '4_3', 768, 576);
    $this->style('d', '1_1', 768, 768);
    $this->freestyle('e', 'freestyle', 768);

    $ratios = $this->info->ratios(768);
    $this->assertSame(['21:9', '16:9', '4:3', '1:1', 'Freestyle'], array_column($ratios, 'label'));
    $this->assertSame(['a', 'b', 'c', 'd', 'e'], array_column($ratios, 'style'));
  }

  /**
   * Tests the aspect ratios with one crop type for all ratios.
   */
  public function testRatiosUnderFocalPoint(): void {
    $this->cropType('focal_point', 'Focal point', NULL);
    $this->style('fp_wide', 'focal_point', 768, 432);
    $this->style('fp_square', 'focal_point', 768, 768);
    $this->style('fp_tall', 'focal_point', 768, 1024);

    $this->assertSame(['16:9', '1:1', '3:4'], array_column($this->info->ratios(768), 'label'));
  }

  /**
   * Tests that a crop type's aspect ratio names and groups its image styles.
   */
  public function testCropTypeRatio(): void {
    $this->cropType('28_9', '28:9', '28:9');
    $this->style('banner_small', '28_9', 768, 247);
    $this->style('banner_medium', '28_9', 1024, 329);
    $this->style('banner_large', '28_9', 1440, 463);

    $ratios = $this->info->ratios(768);
    $this->assertSame(['28:9'], array_column($ratios, 'label'));
    $this->assertSame(['banner_small'], array_column($ratios, 'style'));
    $this->assertSame('28:9 1024×329', $this->info->styleLabels()['banner_medium']['short']);
  }

  /**
   * Tests that the image style nearest the target width is chosen.
   */
  public function testNearestWidth(): void {
    $this->cropType('16_9', '16:9', '16:9');
    $this->style('small', '16_9', 320, 180);
    $this->style('medium', '16_9', 768, 432);
    $this->style('large', '16_9', 1200, 675);

    $this->assertSame(['small'], array_column($this->info->ratios(320), 'style'));
    $this->assertSame(['medium'], array_column($this->info->ratios(768), 'style'));
    $this->assertSame(['large'], array_column($this->info->ratios(1100), 'style'));
  }

  /**
   * Tests that image styles not in use are excluded.
   */
  public function testStylesInUse(): void {
    $this->cropType('16_9', '16:9', '16:9');
    $this->style('live', '16_9', 768, 432);
    $this->style('orphan', '16_9', 760, 428);
    $this->display('live');

    $ratios = $this->info->ratios(768);
    $this->assertSame(['live'], array_column($ratios, 'style'));
    $this->assertSame([['id' => '16_9', 'label' => '16:9', 'styles' => ['live']]], $this->info->usedCropTypes());
  }

  /**
   * Creates an entity view display using an image style.
   *
   * @param string $style
   *   The image style ID.
   */
  protected function display(string $style): void {
    \Drupal::entityTypeManager()->getStorage('entity_view_display')->create([
      'targetEntityType' => 'user',
      'bundle' => 'user',
      'mode' => 'default',
      'status' => TRUE,
      'content' => [
        'picture' => [
          'type' => 'image',
          'weight' => 0,
          'region' => 'content',
          'settings' => ['image_style' => $style],
          'third_party_settings' => [],
        ],
      ],
    ])->save();
  }

  /**
   * Creates an image style with a crop effect and a width-only scale effect.
   *
   * @param string $id
   *   The image style ID.
   * @param string $crop_type
   *   The crop type.
   * @param int $width
   *   The width in pixels.
   */
  protected function freestyle(string $id, string $crop_type, int $width): void {
    $style = ImageStyle::create(['name' => $id, 'label' => 'Style ' . $id]);
    $style->addImageEffect(['id' => 'crop_crop', 'weight' => 1, 'data' => ['crop_type' => $crop_type]]);
    $style->addImageEffect(['id' => 'image_scale', 'weight' => 2, 'data' => ['width' => $width, 'height' => NULL]]);
    $style->save();
  }

}
