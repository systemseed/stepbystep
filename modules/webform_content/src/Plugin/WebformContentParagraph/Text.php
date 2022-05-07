<?php

namespace Drupal\webform_content\Plugin\WebformContentParagraph;

use Drupal\webform_content\WebformContentParagraphPluginBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Simple text field.
 *
 * @WebformContentParagraph(
 *   id = "text",
 *   label = @Translation("Text"),
 *   description = @Translation("Simple text field.")
 * )
 */
class Text extends WebformContentParagraphPluginBase {

  /**
   * {@inheritdoc}
   */
  public function addElements(EntityInterface $paragraph, array &$elements, $langcode) {
    $elements[$this->getElementKey($paragraph)] = [
      '#type' => 'textfield',
      '#title' => $paragraph->webform_content_title->value,
      '#required' => $paragraph->webform_content_required->value,
      '#maxlength' => $paragraph->webform_content_maxlength->value,
    ];
  }

}
