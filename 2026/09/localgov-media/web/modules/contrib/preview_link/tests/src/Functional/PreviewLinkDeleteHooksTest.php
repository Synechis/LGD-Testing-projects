<?php

declare(strict_types=1);

namespace Drupal\Tests\preview_link\Functional;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_test\Entity\EntityTestRevPub;
use Drupal\preview_link\Entity\PreviewLink;
use Drupal\preview_link\Entity\PreviewLinkInterface;
use Drupal\preview_link\Hook\PreviewLinkDeleteHooks;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests preview links are kept consistent with the entities they unlock.
 */
#[Group('preview_link')]
#[RunTestsInSeparateProcesses]
#[CoversClass(PreviewLinkDeleteHooks::class)]
final class PreviewLinkDeleteHooksTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['preview_link', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    \Drupal::configFactory()->getEditable('preview_link.settings')
      ->set('enabled_entity_types', [
        'entity_test_revpub' => [],
      ])
      ->save();
  }

  /**
   * A preview link is deleted when its only referenced entity is deleted.
   */
  public function testPreviewLinkDelete(): void {
    $entity = EntityTestRevPub::create();
    $entity->save();
    $previewLink = $this->createPreviewLinkForEntity($entity);
    $previewLinkId = $previewLink->id();

    $entity->delete();

    $this->assertNull(PreviewLink::load($previewLinkId));
  }

  /**
   * A multi-entity preview link removes the deleted reference.
   */
  public function testPreviewLinkDeleteMultipleEntities(): void {
    $entity = EntityTestRevPub::create();
    $entity->save();
    $otherEntity = EntityTestRevPub::create();
    $otherEntity->save();
    $previewLink = $this->createPreviewLinkForEntity($entity);
    $previewLink->addEntity($otherEntity)->save();
    $previewLinkId = $previewLink->id();

    $entity->delete();

    $reloaded = PreviewLink::load($previewLinkId);
    $this->assertInstanceOf(PreviewLinkInterface::class, $reloaded);
    $remainingIds = array_map(
      static fn (ContentEntityInterface $entity): string => $entity->getEntityTypeId() . ':' . $entity->id(),
      $reloaded->getEntities(),
    );
    $this->assertSame(['entity_test_revpub:' . $otherEntity->id()], $remainingIds);
  }

  /**
   * A multi-entity preview link is deleted once all its entities are gone.
   */
  public function testPreviewLinkDeleteMultipleHostEntities(): void {
    $entity = EntityTestRevPub::create();
    $entity->save();
    $otherEntity = EntityTestRevPub::create();
    $otherEntity->save();
    $previewLink = $this->createPreviewLinkForEntity($entity);
    $previewLink->addEntity($otherEntity)->save();
    $previewLinkId = $previewLink->id();

    $entity->delete();
    $this->assertNotNull(PreviewLink::load($previewLinkId));

    $otherEntity->delete();
    $this->assertNull(PreviewLink::load($previewLinkId));
  }

  /**
   * Creates a preview link for an entity.
   */
  protected function createPreviewLinkForEntity(ContentEntityInterface $entity): PreviewLinkInterface {
    $previewLink = PreviewLink::create()->addEntity($entity);
    $previewLink->save();
    return $previewLink;
  }

}
