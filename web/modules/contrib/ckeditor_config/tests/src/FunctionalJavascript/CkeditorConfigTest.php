<?php

namespace Drupal\Tests\ckeditor_config\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\ckeditor\Traits\CKEditorTestTrait;

/**
 * Verify settings form validation, submission, and storage.
 *
 * @group ckeditor_congfig
 */
class CkeditorConfigTest extends WebDriverTestBase {

  use CKEditorTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'ckeditor',
    'ckeditor_config',
    'editor',
    'filter',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a node type for testing.
    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    $field_storage = FieldStorageConfig::loadByName('node', 'body');

    // Create a body field instance for the 'page' node type.
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'label' => 'Body',
      'settings' => ['display_summary' => TRUE],
      'required' => TRUE,
    ])->save();

    // Assign widget settings for the 'default' form mode.
    EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('body', ['type' => 'text_textarea_with_summary'])
      ->save();

    $filtered_html_format = FilterFormat::create([
      'format' => 'testing_text_format',
      'name' => 'Testing Text Format',
      'weight' => 0,
      'filters' => [],
    ]);
    $filtered_html_format->save();

    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer filters',
      'administer nodes',
      'create page content',
      'use text format testing_text_format',
    ]));
  }

  /**
   * Verify settings form validation, submission, and storage.
   */
  public function testConfigStorage() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Navigate to configuration page for testing text format.
    $this->drupalGet("admin/config/content/formats/manage/testing_text_format");
    $page->fillField('edit-editor-editor', 'ckeditor');
    $assert_session->assertWaitOnAjaxRequest();

    // Enter a value that will fail JSON syntax validation.
    $this->clickLink('CKEditor custom configuration');
    $test_value = 'forcePasteAsPlainText = true' . PHP_EOL . 'pasteFromWordPromptCleanup false' . PHP_EOL . 'removePlugins = "font"' . PHP_EOL . 'tabIndex = 3';
    $page->fillField('editor[settings][plugins][customconfig][ckeditor_custom_config]', $test_value);
    $page->pressButton('Save configuration');
    $assert_session->waitForElement('xpath', '//div[@role="alert"]');
    $assert_session->elementTextContains('xpath', '//div[@role="alert"]', 'The configuration syntax on line 2 is incorrect.');

    // Enter a value that will pass JSON syntax validation.
    $this->clickLink('CKEditor custom configuration');
    $test_value = 'forcePasteAsPlainText = true' . PHP_EOL . 'pasteFromWordPromptCleanup = false' . PHP_EOL . 'removePlugins = "font"' . PHP_EOL . 'tabIndex = 3';
    $page->fillField('editor[settings][plugins][customconfig][ckeditor_custom_config]', $test_value);
    $page->pressButton('Save configuration');
    $assert_session->elementTextContains('xpath', '//div[@aria-label="Status message"]', 'The text format Testing Text Format has been updated.');

    $this->drupalGet("admin/config/content/formats/manage/testing_text_format");
    // Enter a value that will fail JSON syntax validation.
    $this->clickLink('CKEditor custom configuration');
    $test_value = 'forcePasteAsPlainText = true' . PHP_EOL . 'pasteFromWordPromptCleanup = false' . PHP_EOL . 'removePlugins = "font"' . PHP_EOL . 'tabIndex = 3' . PHP_EOL . 'format_h2 = { element: "h2", attributes: { "class": "contentTitle2" } }';
    $page->fillField('editor[settings][plugins][customconfig][ckeditor_custom_config]', $test_value);
    $page->pressButton('Save configuration');
    $assert_session->waitForElement('xpath', '//div[@role="alert"]');
    $assert_session->elementTextContains('xpath', '//div[@role="alert"]', 'The configuration value on line 5 is not valid JSON.');

    // Enter a value that will pass JSON syntax validation.
    $this->clickLink('CKEditor custom configuration');
    $test_value = 'forcePasteAsPlainText = true' . PHP_EOL . 'pasteFromWordPromptCleanup = false' . PHP_EOL . 'removePlugins = "font"' . PHP_EOL . 'tabIndex = 3' . PHP_EOL . 'format_h2 = { "element": "h2", "attributes": { "class": "contentTitle2" } }';
    $page->fillField('editor[settings][plugins][customconfig][ckeditor_custom_config]', $test_value);
    $page->pressButton('Save configuration');
    $assert_session->elementTextContains('xpath', '//div[@aria-label="Status message"]', 'The text format Testing Text Format has been updated.');

    // Verify submitted value is same as value stored in config.
    $editor = editor_load('testing_text_format');
    $settings = $editor->getSettings();
    $stored_value = $settings['plugins']['customconfig']['ckeditor_custom_config'];
    // Normalize line endings in config value.
    $stored_value = str_replace(["\r\n", "\n", "\r"], PHP_EOL, $stored_value);
    $this->assertIdentical($test_value, $stored_value);

    // Verify submitted values are rendered in drupal-settings-json.
    $this->drupalGet('node/add/page');
    $this->waitForEditor();
    $settings = $this->getDrupalSettings();
    $settings = $settings['editor']['formats']['testing_text_format']['editorSettings'];
    $this->assertIdentical($settings['forcePasteAsPlainText'], TRUE);
    // This value overrides the default value set by the CKEditor's
    // Internal plugin.
    $this->assertIdentical($settings['pasteFromWordPromptCleanup'], FALSE);
    $this->assertIdentical($settings['removePlugins'], 'font');
    $this->assertIdentical($settings['tabIndex'], 3);
    $test_value = [
      'attributes' => [
        'class' => 'contentTitle2',
      ],
      'element' => 'h2',
    ];
    $this->assertIdentical($settings['format_h2'], $test_value);
  }

}
