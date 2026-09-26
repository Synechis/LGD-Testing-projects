<?php

namespace Drupal\Tests\localgov_publications\Functional;

use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Url;

/**
 * Functional tests for our link modifications.
 *
 * @group localgov_publications
 */
class ChildLinkTest extends PublicationsTestBase {

  /**
   * Test the 'Add child page' link on a publication goes to the right type.
   */
  public function testAddChildPageLink() {

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

    $node = $this->drupalCreateNode([
      'type' => 'localgov_publication_page',
      'title' => 'Test publication page',
      'localgov_publication_content' => [
        'target_id' => $text_paragraph->id(),
        'target_revision_id' => $text_paragraph->getRevisionId(),
      ],
      'book' => [
        'bid' => 'new',
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->drupalLogin($adminUser);
    $this->drupalGet('/node/' . $node->id());
    $this->assertSession()->responseContains('Add child page');
    $publication_add_path = Url::fromUserInput('/node/add/localgov_publication_page')->toString();
    $this->assertSession()->linkByHrefExists($publication_add_path . '?parent=' . $node->id());

  }

}
