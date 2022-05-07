<?php

namespace Drupal\webform_content\Plugin\WebformContentParagraph;

use Drupal\webform_content\WebformContentParagraphPluginBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Simple Date field.
 *
 * @WebformContentParagraph(
 *   id = "date",
 *   label = @Translation("Date"),
 *   description = @Translation("Date field.")
 * )
 */
class Date extends WebformContentParagraphPluginBase {

  /**
   * {@inheritdoc}
   */
  public function addElements(EntityInterface $paragraph, array &$elements, $langcode) {
    $elements[$this->getElementKey($paragraph)] = [
      '#type' => 'date',
      '#title' => $paragraph->webform_content_title->value,
      '#required' => $paragraph->webform_content_required->value,
    ];
  }

}
