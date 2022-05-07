<?php

namespace Drupal\sbs_questionnaire\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with a close button.
 *
 * @Block(
 *   id = "sbs_questionnaire_close",
 *   admin_label = @Translation("Close button"),
 *   category = @Translation("SBS questionnaire")
 * )
 */
class CloseButton extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
      '#markup' => '<a href="/sessions" class="close-navigation"><span class="material-icons">close</span></a>',
    ];
    return $build;
  }

}
