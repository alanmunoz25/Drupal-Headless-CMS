<?php

namespace Drupal\country_import\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides a country_import controller.
 */
class InfoController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
    $db_name = get_current_db_name();
    return [
      '#type' => 'markup',
      '#markup' => "db_name: {$db_name}",
    ];

  }

}
