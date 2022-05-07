<?php

namespace Drupal\sbs_navigation\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a back button block.
 *
 * @Block(
 *   id = "sbs_navigation_back_button",
 *   admin_label = @Translation("Back button"),
 *   category = @Translation("SBS navigation")
 * )
 */
class BackButton extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
      '#prefix' => '<a href="#" class="back-navigation"><span class="material-icons">arrow_back</span>',
      '#markup' => $this->t('Back'),
      '#suffix' => '</a>',
      '#attached' => ['library' => ['sbs_navigation/back-button']],
    ];
    return $build;
  }

}
