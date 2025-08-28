<?php

namespace Drupal\style_guide\Controller;

use Drupal\Core\Controller\ControllerBase;

class StyleGuideController extends ControllerBase {

  public function content() {
    $persons = [];
    for ($i = 1; $i <= 10; $i++) {
      $persons[] = [
        'name' => "Person $i",
        'status' => "Developer $i",
        'role' => "Role $i",
        'image' => "https://i.pravatar.cc/300?img=$i",
      ];
    }

    return [
      '#theme' => 'style_guide',
      '#persons' => $persons,
    ];
  }

}
