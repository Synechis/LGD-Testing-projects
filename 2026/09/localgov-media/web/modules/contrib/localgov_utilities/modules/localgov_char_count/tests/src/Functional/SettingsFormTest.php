<?php

namespace Drupal\Tests\localgov_char_count\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\localgov_char_count\Form\CharacterCounterSettingsForm;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests the character count settings form.
 *
 * @group localgov_char_count
 */
class SettingsFormTest extends WebDriverTestBase {

  use ContentTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'field_ui',
    'localgov_char_count',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a LocalGov content type with title and body fields.
    $this->createContentType([
      'type' => 'localgov_page',
      'title' => 'LocalGov Page',
    ]);

    // Drupal 11.3 no longer adds body fields to content types by default.
    // Check that the body field exists on localgov_page and add it if not.
    if (!FieldConfig::loadByName('node', 'localgov_page', 'body')) {
      if (!FieldStorageConfig::loadByName('node', 'body')) {
        FieldStorageConfig::create([
          'field_name' => 'body',
          'entity_type' => 'node',
          'type' => 'text_with_summary',
        ])->save();
      }
      FieldConfig::create([
        'field_name' => 'body',
        'field_type' => 'text_with_summary',
        'entity_type' => 'node',
        'bundle' => 'localgov_page',
        'label' => 'Body',
      ])->save();
    }

    // Make sure display_summary and required_summary are enabled.
    $body_field = FieldConfig::loadByName('node', 'localgov_page', 'body');
    $body_field->setSetting('display_summary', TRUE);
    $body_field->setSetting('required_summary', TRUE);
    $body_field->save();

    // Make sure the body field uses the "text_textarea_with_summary" widget.
    $form_display = EntityFormDisplay::collectRenderDisplay(
      \Drupal::entityTypeManager()->getStorage('node')->create(['type' => 'localgov_page']),
      'default'
    );
    $form_display->setComponent('body', [
      'type' => 'text_textarea_with_summary',
      'weight' => 1,
      'settings' => ['show_summary' => TRUE],
    ])->save();
  }

  /**
   * Test settings form.
   */
  public function testSettingsForm(): void {
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer nodes',
      'administer site configuration',
      'bypass node access',
      'localgov character counting admin',
    ]);
    $this->drupalLogin($admin_user);

    $title_counter_message = '0 / ' . CharacterCounterSettingsForm::DEFAULT_TITLE_LENGTH . ' characters';
    $summary_counter_message = '0 / ' . CharacterCounterSettingsForm::DEFAULT_SUMMARY_LENGTH . ' characters';

    // Enable character counting.
    $this->drupalGet(Url::fromRoute('localgov_char_count.character_counter_settings'));
    $this->getSession()->getPage()->hasUncheckedField('fields[localgov_page][title]');
    $this->getSession()->getPage()->hasUncheckedField('fields[localgov_page][body]');
    $this->getSession()->getPage()->checkField('fields[localgov_page][title]');
    $this->getSession()->getPage()->checkField('fields[localgov_page][body]');
    $this->getSession()->getPage()->pressButton('Apply configuration changes');
    $this->assertSession()->addressEquals(Url::fromRoute('localgov_char_count.character_counter_settings'));
    $this->getSession()->getPage()->hasCheckedField('fields[localgov_page][title]');
    $this->getSession()->getPage()->hasCheckedField('fields[localgov_page][body]');

    // Check node edit form does include character counter.
    $this->drupalGet('/node/add/localgov_page');
    $this->assertSession()->elementTextContains('css', '.form-item-title-0-value', $title_counter_message);
    $this->assertSession()->elementTextContains('css', '.form-item-body-0-summary', $summary_counter_message);

    // Disable character counting.
    $this->drupalGet(Url::fromRoute('localgov_char_count.character_counter_settings'));
    $this->getSession()->getPage()->uncheckField('fields[localgov_page][title]');
    $this->getSession()->getPage()->uncheckField('fields[localgov_page][body]');
    $this->getSession()->getPage()->pressButton('Apply configuration changes');

    // Check node edit form doesn't include character counter.
    $this->drupalGet('/node/add/localgov_page');
    $this->assertSession()->elementTextNotContains('css', '.form-item-title-0-value', $title_counter_message);
    $this->assertSession()->elementTextNotContains('css', '.form-item-body-0-summary', $summary_counter_message);
  }

}
