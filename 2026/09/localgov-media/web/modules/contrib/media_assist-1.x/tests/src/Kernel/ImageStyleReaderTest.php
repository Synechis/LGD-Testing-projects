<?php

declare(strict_types=1);

namespace Drupal\Tests\media_assist\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\crop\Entity\CropType;
use Drupal\image\Entity\ImageStyle;
use Drupal\media_assist\ImageStyleReader;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the image style reader.
 *
 * @group media_assist
 */
#[Group('media_assist')]
#[RunTestsInSeparateProcesses]
class ImageStyleReaderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'file',
    'image',
    'crop',
    'focal_point',
    'media_assist',
  ];

  /**
   * The image style reader.
   *
   * @var \Drupal\media_assist\ImageStyleReader
   */
  protected $reader;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('crop');
    $this->installConfig(['focal_point']);
    $this->reader = $this->container->get('media_assist.image_style_reader');
    CropType::create(['id' => '16_9', 'label' => '16:9', 'aspect_ratio' => '16:9'])->save();
    CropType::create(['id' => '8_7', 'label' => '8:7', 'aspect_ratio' => '8:7'])->save();
    CropType::create(['id' => 'freestyle', 'label' => 'Freestyle', 'aspect_ratio' => ''])->save();
  }

  /**
   * Creates an image style.
   *
   * @param string $id
   *   The image style ID.
   * @param array $effects
   *   The effects, each an array of the plugin ID and its data, in order.
   */
  protected function style(string $id, array $effects): void {
    $style = ImageStyle::create(['name' => $id, 'label' => 'Style ' . $id]);
    foreach ($effects as $weight => [$plugin_id, $data]) {
      $style->addImageEffect(['id' => $plugin_id, 'weight' => $weight, 'data' => $data]);
    }
    $style->save();
  }

  /**
   * Tests reading a crop_crop effect followed by a size effect.
   */
  public function testManualCrop(): void {
    $this->style('wide', [
      ['crop_crop', ['crop_type' => '16_9']],
      ['image_scale_and_crop', ['width' => 768, 'height' => 432]],
    ]);
    $this->style('free', [
      ['crop_crop', ['crop_type' => 'freestyle']],
      ['image_scale', ['width' => 992, 'height' => NULL]],
    ]);
    $this->style('crop_only', [
      ['crop_crop', ['crop_type' => '16_9']],
    ]);

    $this->assertSame(
      ['crop_type' => '16_9', 'width' => 768, 'height' => 432, 'ratio' => '16:9'],
      $this->reader->read('wide')
    );
    $this->assertSame(
      ['crop_type' => 'freestyle', 'width' => 992, 'height' => NULL, 'ratio' => NULL],
      $this->reader->read('free')
    );
    $this->assertSame(
      ['crop_type' => '16_9', 'width' => NULL, 'height' => NULL, 'ratio' => '16:9'],
      $this->reader->read('crop_only')
    );
  }

  /**
   * Tests reading the focal point effects.
   */
  public function testFocalPoint(): void {
    $this->style('fp_scale_and_crop', [
      ['focal_point_scale_and_crop', ['width' => 1152, 'height' => 648]],
    ]);
    $this->style('fp_crop', [
      ['focal_point_crop', ['width' => 400, 'height' => 400]],
    ]);
    $this->style('fp_by_width', [
      ['focal_point_crop_by_width', ['width' => 600, 'height' => NULL]],
    ]);
    $this->style('fp_nearly', [
      ['focal_point_scale_and_crop', ['width' => 266, 'height' => 236]],
    ]);

    $this->assertSame(
      ['crop_type' => 'focal_point', 'width' => 1152, 'height' => 648, 'ratio' => '16:9'],
      $this->reader->read('fp_scale_and_crop')
    );
    $this->assertSame(
      ['crop_type' => 'focal_point', 'width' => 400, 'height' => 400, 'ratio' => '1:1'],
      $this->reader->read('fp_crop')
    );
    $this->assertSame(
      ['crop_type' => 'focal_point', 'width' => 600, 'height' => NULL, 'ratio' => NULL],
      $this->reader->read('fp_by_width')
    );
    $this->assertSame('8:7', $this->reader->read('fp_nearly')['ratio']);
  }

  /**
   * Tests that the dimensions snap to the aspect ratio of a crop type.
   */
  public function testCropTypeRatio(): void {
    $this->style('nearly', [
      ['crop_crop', ['crop_type' => '8_7']],
      ['image_scale_and_crop', ['width' => 266, 'height' => 236]],
    ]);
    $this->style('other_shape', [
      ['crop_crop', ['crop_type' => '16_9']],
      ['image_scale_and_crop', ['width' => 300, 'height' => 300]],
    ]);
    $this->style('no_crop', [
      ['image_scale_and_crop', ['width' => 532, 'height' => 472]],
    ]);

    $this->assertSame('8:7', $this->reader->read('nearly')['ratio']);
    $this->assertSame('1:1', $this->reader->read('other_shape')['ratio']);
    $this->assertSame('8:7', $this->reader->read('no_crop')['ratio']);
    $this->assertSame(['16_9' => '16:9', '8_7' => '8:7'], $this->reader->cropTypeRatios());
  }

  /**
   * Tests styles without a crop effect and styles that do not exist.
   */
  public function testNoCrop(): void {
    $this->style('plain', [
      ['image_scale', ['width' => 400, 'height' => NULL]],
    ]);

    $this->assertSame(
      ['crop_type' => NULL, 'width' => 400, 'height' => NULL, 'ratio' => NULL],
      $this->reader->read('plain')
    );
    $this->assertNull($this->reader->read('missing'));
  }

  /**
   * Tests ratioOf().
   */
  public function testRatioOf(): void {
    $this->assertSame('21:9', ImageStyleReader::ratioOf(768, 329));
    $this->assertSame('16:9', ImageStyleReader::ratioOf(768, 432));
    $this->assertSame('4:3', ImageStyleReader::ratioOf(768, 576));
    $this->assertSame('1:1', ImageStyleReader::ratioOf(768, 768));
    $this->assertSame('5:2', ImageStyleReader::ratioOf(1000, 400));
  }

  /**
   * Tests orientationOf().
   */
  public function testOrientationOf(): void {
    $this->assertSame('landscape', ImageStyleReader::orientationOf(768, 432));
    $this->assertSame('portrait', ImageStyleReader::orientationOf(432, 768));
    $this->assertSame('square', ImageStyleReader::orientationOf(400, 400));
    $this->assertSame('freestyle', ImageStyleReader::orientationOf(400, NULL));
    $this->assertNull(ImageStyleReader::orientationOf(NULL, NULL));
  }

}
