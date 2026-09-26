<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_hierarchy\Kernel;

use Drupal\entity_hierarchy\Storage\ParentEntityRevisionUpdater;
use Drupal\entity_test\Entity\EntityTestRev;

/**
 * Defines a class for testing flushing original keys.
 *
 * @covers \Drupal\entity_hierarchy\Storage\ParentEntityRevisionUpdater::flushOriginalKeys
 *
 * @group entity_hierarchy
 */
class FlushOriginalKeysTest extends EntityHierarchyKernelTestBase {

  /**
   * {@inheritdoc}
   */
  const ENTITY_TYPE = 'entity_test_rev';

  /**
   * Test flushing original keys.
   */
  public function testFlushOriginalKeys(): void {
    $this->parent->setNewRevision(TRUE);
    $this->parent->save();
    self::assertNotEmpty(ParentEntityRevisionUpdater::getOriginalKeys());
    ParentEntityRevisionUpdater::flushOriginalKeys();
    self::assertEmpty(ParentEntityRevisionUpdater::getOriginalKeys());
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreateTestEntity(array $values) {
    return EntityTestRev::create($values);
  }

}
