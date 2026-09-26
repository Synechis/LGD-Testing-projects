<?php

namespace Drupal\Tests\localgov_publications\Functional;

/**
 * Tests LocalGov Publications cover page.
 *
 * @group localgov_publications
 */
class PublicationCoverPageTest extends PublicationsTestBase {

  /**
   * Verifies basic functionality with all modules.
   */
  public function testPublicationCoverPageFields() {

    $adminUser = $this->drupalCreateUser([
      'bypass node access',
      'administer nodes',
      'administer node fields',
    ]);

    $this->drupalLogin($adminUser);

    // Check publication page fields.
    $this->drupalGet('/admin/structure/types/manage/localgov_publication_cover_page/fields');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('body');
    $this->assertSession()->pageTextContains('localgov_documents');
    $this->assertSession()->pageTextContains('localgov_published_date');
    $this->assertSession()->pageTextContains('localgov_updated_date');
  }

}
