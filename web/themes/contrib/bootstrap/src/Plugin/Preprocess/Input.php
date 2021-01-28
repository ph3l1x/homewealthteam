<?php

namespace Drupal\bootstrap\Plugin\Preprocess;

use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "input" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("input")
 */
class Input extends PreprocessBase implements PreprocessInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocessElement(Element $element, Variables $variables) {
    // Autocomplete.
    if ($route = $element->getProperty('autocomplete_route_name')) {
      $variables['autocomplete'] = TRUE;
    }

    // Create variables for #input_group and #input_group_button flags.
    $variables['input_group'] = $element->getProperty('input_group') || $element->getProperty('input_group_button');

    // Create variables for #input_group_button_processed that
    // button has been rendered as #filed_suffix of other element.
    $variables['input_group_button_processed'] = $variables->element->getProperty('input_group_button_processed');

    // Map the element properties.
    $variables->map([
      'attributes' => 'attributes',
      'icon' => 'icon',
      'type' => 'type',
    ]);

    // Just map the prefix and suffix when input_group property enabled.
    if ($variables['input_group']) {
      $variables->map([
        'field_prefix' => 'prefix',
        'field_suffix' => 'suffix',
      ]);
    }

    // Ensure attributes are proper objects.
    $this->preprocessAttributes();
  }

}
