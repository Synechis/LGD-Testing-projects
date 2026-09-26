<?php

namespace Drupal\Tests\localgov_publications\Functional;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Publication navigation tests.
 *
 * @group localgov_publications
 */
class PublicationPageNavigationTest extends PublicationsTestBase {

  /**
   * Test that links and nav are present on a multi-page publication.
   */
  public function testPreviousNextLinks() {
    $adminUser = $this->drupalCreateUser([], NULL, TRUE);

    // Create a text paragraph.
    $text_paragraph = Paragraph::create([
      'type' => 'localgov_text',
      'localgov_text' => [
        'value' => '<p>Content</p>',
        'format' => 'wysiwyg',
      ],
    ]);
    $text_paragraph->save();

    $node_parent = $this->drupalCreateNode([
      'type' => 'localgov_publication_page',
      'title' => 'Publication parent page',
      'localgov_publication_content' => [
        'target_id' => $text_paragraph->id(),
        'target_revision_id' => $text_paragraph->getRevisionId(),
      ],
      'book' => [
        'bid' => 'new',
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);

    $node_child_one = $this->drupalCreateNode([
      'type' => 'localgov_publication_page',
      'title' => 'Publication child page one',
      'localgov_publication_content' => [
        'target_id' => $text_paragraph->id(),
        'target_revision_id' => $text_paragraph->getRevisionId(),
      ],
      'book' => [
        'bid' => $node_parent->id(),
        'pid' => $node_parent->id(),
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->drupalCreateNode([
      'type' => 'localgov_publication_page',
      'title' => 'Publication child page two',
      'localgov_publication_content' => [
        'target_id' => $text_paragraph->id(),
        'target_revision_id' => $text_paragraph->getRevisionId(),
      ],
      'book' => [
        'bid' => $node_parent->id(),
        'pid' => $node_parent->id(),
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->drupalLogin($adminUser);
    $this->drupalGet('/node/' . $node_child_one->id());

    $prevLinks = $this->xpath('//a[contains(@class, "lgd-prev-next__link--prev")]');
    $prevLink = reset($prevLinks);
    $prevLinkPath = Url::fromUserInput('/publication-parent-page')->toString();
    $this->assertSame($prevLinkPath, $prevLink->getAttribute('href'));

    $nextLinks = $this->xpath('//a[contains(@class, "lgd-prev-next__link--next")]');
    $nextLink = reset($nextLinks);
    $nextLinkPath = Url::fromUserInput('/publication-parent-page/publication-child-page-two')->toString();
    $this->assertSame($nextLinkPath, $nextLink->getAttribute('href'));

    // This is the default title of the publication navigation block.
    $this->assertSession()->pageTextContains('Publication navigation');
  }

  /**
   * Test the 'book navigation' block is not displayed on single page books.
   */
  public function testBookNavigationIsNotDisplayed() {
    $node_parent = $this->drupalCreateNode([
      'type' => 'localgov_publication_page',
      'title' => 'Publication parent page',
      'body' => [
        'summary' => '<p>Content</p>',
        'value' => '<p>Content</p>',
        'format' => 'wysiwyg',
      ],
      'book' => [
        'bid' => 'new',
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->drupalGet('/node/' . $node_parent->id());

    // This is the default title of the publication navigation block.
    // It shouldn't show on a single page publication.
    $this->assertSession()->pageTextNotContains('Publication navigation');
  }

}
