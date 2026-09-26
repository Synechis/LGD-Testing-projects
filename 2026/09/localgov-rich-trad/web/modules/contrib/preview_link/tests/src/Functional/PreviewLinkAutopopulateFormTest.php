<?php

declare(strict_types=1);

namespace Drupal\Tests\preview_link\Functional;

use Drupal\preview_link\Entity\PreviewLink;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the auto-populate features on the preview link form.
 *
 * @group preview_link
 */
final class PreviewLinkAutopopulateFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'preview_link',
    'preview_link_test_autopopulate',
  ];

  /**
   * Tests the auto-populate button on the preview link form.
   */
  public function testReferenceUnpublishedNode(): void {
    $this->createContentType(['type' => 'page']);
    $node1 = $this->createNode([
      'title' => 'node1',
    ]);
    $this->createNode([
      'title' => 'node2',
    ]);
    $preview_link = PreviewLink::create()->addEntity($node1);
    $preview_link->save();
    $this->assertCount(1, $preview_link->getEntities());

    $this->drupalLogin($this->createUser([
      'generate preview links',
      'access content',
    ]));

    $this->drupalGet($node1->toUrl('preview-link-generate'));
    $this->assertSession()->buttonExists('Add all page nodes');
    $this->submitForm([], 'Add all page nodes');

    $preview_link = $this->container->get('entity_type.manager')
      ->getStorage('preview_link')
      ->load($preview_link->id());
    assert($preview_link instanceof PreviewLink);
    $this->assertCount(2, $preview_link->getEntities());
  }

}
