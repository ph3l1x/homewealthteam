<?php

namespace Drupal\ckeditor_config\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "customconfig" plugin.
 *
 * @CKEditorPlugin(
 *   id = "customconfig",
 *   label = @Translation("CKEditor custom configuration")
 * )
 */
class CustomConfig extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, CKEditorPluginContextualInterface {

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $config = [];
    $settings = $editor->getSettings();
    if (!isset($settings['plugins']['customconfig']['ckeditor_custom_config'])) {
      return $config;
    }

    $custom_config = $settings['plugins']['customconfig']['ckeditor_custom_config'];

    // Check if custom config is populated.
    if (!empty($custom_config)) {
      // Build array from string.
      $config_array = preg_split('/\R/', $custom_config);

      // Loop through config lines and append to editorSettings.
      foreach ($config_array as $config_value) {
        $exploded_value = explode(" = ", $config_value);
        $key = $exploded_value[0];
        $value = $exploded_value[1];

        // Convert true/false strings to boolean values.
        if (strcasecmp($value, 'true') == 0
          || strcasecmp($value, 'false') == 0
          ) {
          $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        // Convert numeric values to integers.
        if (is_numeric($value)) {
          $value = (int) $value;
        }

        // If the value is boolean, then don't try to process as JSON.
        if (is_bool($value)) {
          $config['ckeditor_custom_config'][$key] = $value;
        }
        // Create JSON string and attempt to decode.
        // This is necessary to convert JSON objects and arrays to
        // PHP arrays, which is the expected return syntax.
        else {
          $json = '{ "' . $key . '": ' . $value . ' }';
          $decoded_json = Json::decode($json, TRUE);

          // If value can be decoded, then append to config.
          if (!is_null($decoded_json)) {
            $config['ckeditor_custom_config'][$key] = $decoded_json[$key];
          }
        }

      }
    }

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {

    $config = ['ckeditor_custom_config' => ''];
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['customconfig'])) {
      $config = $settings['plugins']['customconfig'];
    }

    // Load Editor settings.
    $settings = $editor->getSettings();

    $form['ckeditor_custom_config'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CKEditor Custom Configuration'),
      '#default_value' => $config['ckeditor_custom_config'],
      '#description' => $this->t('Each line may contain a CKEditor configuration setting formatted as "<code>[setting.name] = [value]</code>" with the value being formatted as valid JSON. See <a href="@url">https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html</a> for more details. Note: the examples in the official documentation are provided in Javascript, not JSON.<br><br>Examples: \'<code>forcePasteAsPlainText = true</code>\', \'<code>forceSimpleAmpersand = false</code>\', \'<code>removePlugins = "font"</code>\', \'<code>tabIndex = 3</code>\', \'<code>format_h2 = { "element": "h2", "attributes": { "class": "contentTitle2" } }</code>\'', ['@url' => 'https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html']),
      '#attached' => [
        'library' => ['ckeditor_config/ckeditor_config.customconfig'],
      ],
      '#element_validate' => [
        [$this, 'validateCustomConfig'],
      ],
    ];

    return $form;
  }

  /**
   * Custom validator for the "custom_config" element in settingsForm().
   */
  public function validateCustomConfig(array $element, FormStateInterface $form_state) {
    // Convert submitted value into an array. Return is empty.
    $config_value = $element['#value'];
    if (empty($config_value)) {
      return;
    }
    $config_array = preg_split('/\R/', $config_value);

    // Loop through lines.
    $i = 1;
    foreach ($config_array as $config_value) {
      // Check that syntax matches "[something] = [something]".
      preg_match('/(.*?) \= (.*)/', $config_value, $matches);
      if (empty($matches)) {
        $form_state->setError($element, $this->t('The configuration syntax on line @line is incorrect. The correct syntax is "[setting.name] = [value]"', ['@line' => $i]));
      }
      // If syntax is valid, then check JSON validity.
      else {
        // Check is value is valid JSON.
        $exploded_value = explode(" = ", $config_value);
        $key = $exploded_value[0];
        $value = $exploded_value[1];

        // Create JSON string and attempt to decode.
        // If invalid, then set error.
        $json = '{ "' . $key . '": ' . $value . ' }';
        $decoded_json = Json::decode($json, TRUE);
        if (is_null($decoded_json)) {
          $form_state->setError($element, $this->t('The configuration value on line @line is not valid JSON.', ['@line' => $i]));
        }
      }

      $i++;
    }
  }

}
