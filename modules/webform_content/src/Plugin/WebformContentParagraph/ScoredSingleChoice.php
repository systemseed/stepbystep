<?php

namespace Drupal\webform_content\Plugin\WebformContentParagraph;

use Drupal\webform_content\WebformContentParagraphPluginBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Single choice radio buttons.
 *
 * @WebformContentParagraph(
 *   id = "scored_single_ch",
 *   label = @Translation("Scored single choice"),
 *   description = @Translation("Single choice radio buttons")
 * )
 */
class ScoredSingleChoice extends WebformContentParagraphPluginBase {

  /**
   * {@inheritdoc}
   */
  public function addElements(EntityInterface $paragraph, array &$elements, $langcode) {
    $options = [];

    foreach ($paragraph->webform_content_choices->referencedEntities() as $choiceParagraph) {
      if ($langcode && $choiceParagraph->hasTranslation($langcode)) {
        $choiceParagraph = $choiceParagraph->getTranslation($langcode);
      }
      $value = $choiceParagraph->webform_content_score->isEmpty() ?
        $choiceParagraph->webform_content_text->value :
        $choiceParagraph->webform_content_score->value;
      $options[$value] = $choiceParagraph->webform_content_text->value;
      // Add the description in the format webform expects.
      if (!$choiceParagraph->webform_content_description->isEmpty()) {
        $options[$value] .= ' -- ' . $choiceParagraph->webform_content_description->value;
      }
    }
    $elements[$this->getElementKey($paragraph)] = [
      '#type' => 'radios',
      '#title' => $paragraph->webform_content_title->value,
      '#required' => $paragraph->webform_content_required->value,
      '#options' => $options,
    ];
  }

}
