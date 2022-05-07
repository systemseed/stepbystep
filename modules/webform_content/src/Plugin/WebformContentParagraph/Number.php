<?php

namespace Drupal\webform_content\Plugin\WebformContentParagraph;

use Drupal\webform_content\WebformContentParagraphPluginBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Simple text field.
 *
 * @WebformContentParagraph(
 *   id = "number",
 *   label = @Translation("Number"),
 *   description = @Translation("Simple Number field.")
 * )
 */
class Number extends WebformContentParagraphPluginBase {

  /**
   * {@inheritdoc}
   */
  public function addElements(EntityInterface $paragraph, array &$elements, $langcode) {
    $element = [
      '#type' => 'number',
      '#min' => $paragraph->webform_content_min->value,
      '#max' => $paragraph->webform_content_max->value,
      '#title' => $paragraph->webform_content_title->value,
      '#required' => $paragraph->webform_content_required->value,
    ];

    // Added workarond with included pattern and type of textfield,
    // because we added custom range between min and max value with
    // setting custom alert message for this type of field.
    if ($paragraph->webform_content_min->value && $paragraph->webform_content_max->value && $paragraph->webform_content_min->value < $paragraph->webform_content_max->value && $paragraph->webform_content_error->value) {
      $numbers = range($paragraph->webform_content_min->value, $paragraph->webform_content_max->value);
      $pattern = implode("|", $numbers);
      $element['#type'] = 'textfield';
      $element['#pattern_error'] = $paragraph->webform_content_error->getString();
      $element['#pattern'] = $pattern;
    }

    $elements[$this->getElementKey($paragraph)] = $element;
  }

}
