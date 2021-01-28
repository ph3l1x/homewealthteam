<?php

namespace Drupal\powrsocialfeed\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "powrsocialfeed" plugin, with a CKEditor.
 *
 * @CKEditorPlugin(
 *   id = "powrsocialfeed",
 *   label = @Translation("powrsocialfeed Plugin")
 * )
 */
class powrsocialfeed extends PluginBase implements CKEditorPluginInterface, CKEditorPluginContextualInterface, CKEditorPluginButtonsInterface {

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'powrsocialfeed') . '/js/plugins/powrsocialfeed/plugin.js';
  }

  /**
   * @return array
   */
  public function getButtons() {
    $powr_socialfeed_icon = drupal_get_path('module', 'powrsocialfeed') . '/js/plugins/powrsocialfeed/icons/socialfeed.png';
    $powr_icon = drupal_get_path('module', 'powrsocialfeed') . '/js/plugins/powrsocialfeed/icons/powr.png';

    return [
      'powr_apps_dropdown' => [
        'label' => t('POWr Plugins'),
        'image' => $powr_icon,
      ],
      'powr_socialfeed' => [
        'label' => t('POWr Social Feed'),
        'image' => $powr_socialfeed_icon,
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
