<?php

declare(strict_types=1);

namespace Drupal\Tests\preview_link\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\preview_link\Entity\PreviewLink;
use Drupal\preview_link_test_autopopulate\Plugin\PreviewLinkAutopopulate\EntityTest as EntityTestPlugin;

/**
 * Preview link auto-populate test.
 *
 * @group preview_link
 * @coversDefaultClass \Drupal\preview_link\PreviewLinkAutopopulatePluginBase
 */
final class PreviewLinkAutopopulateTest extends PreviewLinkBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'preview_link',
    'preview_link_test_autopopulate',
  ];

  /**
   * The entity_test plugin.
   *
   * @var \Drupal\preview_link_test_autopopulate\Plugin\PreviewLinkAutopopulate\EntityTest
   */
  protected EntityTestPlugin $entityTestPlugin;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');
    $this->entityTestPlugin = $this->container
      ->get('plugin.manager.preview_link_autopopulate')
      ->createInstance('entity_test');
  }

  /**
   * Test get and set entity.
   *
   * @covers ::getEntity
   * @covers ::setEntity
   */
  public function testGetSetEntity(): void {
    $entity = EntityTest::create();
    $this->entityTestPlugin->setEntity($entity);
    $this->assertSame($entity, $this->entityTestPlugin->getEntity());
  }

  /**
   * Test get label.
   *
   * @covers ::getLabel
   */
  public function testGetLabel(): void {
    $this->assertEquals('Add all entity_test', $this->entityTestPlugin->getLabel());
  }

  /**
   * Test get preview entities.
   *
   * @covers ::getPreviewEntities
   */
  public function testGetPreviewEntities(): void {
    $this->assertCount(0, $this->entityTestPlugin->getPreviewEntities());
    $entity = EntityTest::create();
    $entity->save();
    $this->entityTestPlugin->setEntity($entity);
    $this->assertCount(1, $this->entityTestPlugin->getPreviewEntities());

    for ($i = 0; $i < 10; $i++) {
      $entity = EntityTest::create();
      $entity->save();
    }
    $this->assertCount(11, $this->entityTestPlugin->getPreviewEntities());
  }

  /**
   * Test is supported.
   *
   * @covers ::isSupported
   */
  public function testIsSupported(): void {
    $this->assertFalse($this->entityTestPlugin->isSupported());
    $supported_entity = EntityTest::create();
    $supported_entity->save();
    $this->entityTestPlugin->setEntity($supported_entity);
    $this->assertTrue($this->entityTestPlugin->isSupported());

    $unsupported_entity = EntityTestRev::create();
    $unsupported_entity->save();
    $this->entityTestPlugin->setEntity($unsupported_entity);
    $this->assertFalse($this->entityTestPlugin->isSupported());
  }

  /**
   * Test populate preview links.
   *
   * @covers ::populatePreviewLinks
   */
  public function testPopulatePreviewLinks(): void {
    $entity = EntityTest::create();
    $entity->save();
    $this->entityTestPlugin->setEntity($entity);
    $preview_link = PreviewLink::create();
    $preview_link->addEntity($entity)->save();
    $this->assertCount(1, $preview_link->getEntities());

    for ($i = 0; $i < 10; $i++) {
      $entity = EntityTest::create();
      $entity->save();
    }
    $this->assertCount(11, $this->entityTestPlugin->getPreviewEntities());
    $this->entityTestPlugin->populatePreviewLinks($preview_link);
    $this->assertCount(11, $preview_link->getEntities());
    $this->entityTestPlugin->populatePreviewLinks($preview_link);
    $this->assertCount(11, $preview_link->getEntities());
  }

}
