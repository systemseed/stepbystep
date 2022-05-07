<?php

namespace Drupal\webform_content\Plugin\WebformContentParagraph;

use Drupal\webform_content\WebformContentParagraphPluginBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Create a division for having one or many questions per page.
 *
 * @WebformContentParagraph(
 *   id = "options_predef",
 *   label = @Translation("Select list from predefined options"),
 *   description = @Translation("Create select form elements from the available sets")
 * )
 */
class SelectListFromOptions extends WebformContentParagraphPluginBase {

  /**
   * {@inheritdoc}
   */
  public function addElements(EntityInterface $paragraph, array &$elements, $langcode) {
    $elements[$this->getElementKey($paragraph)] = [
      '#type' => 'select',
      '#title' => $paragraph->webform_content_title->value,
      '#options' => $paragraph->webform_content_option_set->target_id,
      '#empty_option' => $paragraph->webform_content_empty_option->value,
      '#required' => $paragraph->webform_content_required->value,
    ];
  }

}
