<?php

namespace Drupal\webform_content\Plugin\WebformContentParagraph;

use Drupal\webform_content\WebformContentParagraphPluginBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Simple text field.
 *
 * @WebformContentParagraph(
 *   id = "display_text",
 *   label = @Translation("Display Text"),
 *   description = @Translation("Show markup.")
 * )
 */
class DisplayText extends WebformContentParagraphPluginBase {

  /**
   * {@inheritdoc}
   */
  public function addElements(EntityInterface $paragraph, array &$elements, $langcode) {
    $elements[$this->getElementKey($paragraph)] = [
      '#type' => 'processed_text',
      '#text' => $paragraph->webform_content_body->value,
      '#format' => $paragraph->webform_content_body->format,
    ];
  }

}
