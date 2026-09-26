<?php

namespace Drupal\Tests\localgov_publications\Functional;

use Drupal\node\NodeInterface;

/**
 * Admin page tests.
 *
 * @group localgov_publications
 */
class AdminTest extends PublicationsTestBase {

  /**
   * Test that publications are not listed on the Book overview page.
   */
  public function testPublicationsAreNotListedOnBookOverview() {
    $bookAdministrator = $this->createUser(['administer book outlines']);
    $this->drupalCreateNode([
      'type' => 'localgov_publication_page',
      'title' => 'Test publication page',
      'status' => NodeInterface::PUBLISHED,
      'book' => [
        'bid' => 'new',
      ],
    ]);
    $this->drupalCreateNode([
      'type' => 'book',
      'title' => 'Test book',
      'status' => NodeInterface::PUBLISHED,
      'book' => [
        'bid' => 'new',
      ],
    ]);
    $this->drupalLogin($bookAdministrator);
    $this->drupalGet('/admin/structure/book');

    $this->assertSession()->linkNotExists('Test publication page');
    $this->assertSession()->linkExists('Test book');
  }

}
