<?php
/**
 * @file
 * Contains \Drupal\hello_world\Controller\HelloController.
 */

namespace Drupal\newz\Controller;

use Drupal\Core\Controller\ControllerBase;



class NewzController extends ControllerBase {


  public function content() {
    return array(
      '#theme' => 'hwtheme',
      '#newz_var' => $this->t('Hello, World!'),
    );
  }
}