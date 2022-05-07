<?php

namespace Drupal\webform_content\Plugin\WebformContentParagraph;

use Drupal\webform_content\WebformContentParagraphPluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Create a division for having one or many questions per page.
 *
 * @WebformContentParagraph(
 *   id = "page_separator",
 *   label = @Translation("Page separator"),
 *   description = @Translation("Create a division for having one or many questions per page")
 * )
 */
class PageSeparator extends WebformContentParagraphPluginBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function addElements(EntityInterface $paragraph, array &$elements, $langcode) {
    $newElements = [];
    $page = [
      '#type' => 'webform_wizard_page',
      '#prev_button_label' => (string) ($paragraph->webform_content_prev_label->value ?? $this->t('Previous')),
      '#next_button_label' => (string) ($paragraph->webform_content_next_label->value ?? $this->t('Next')),
    ];
    foreach ($elements as $elementKey => $element) {
      // Skip setting that previous separators might have set.
      if ($elementKey === '#wrap_elements_in_last_page') {
        continue;
      }
      if ($element['#type'] === 'webform_wizard_page') {
        $newElements[$elementKey] = $element;
      }
      else {
        $page[$elementKey] = $element;
        // The element in the page has a title, use that as title of the page.
        if (isset($element['#title'])) {
          $page['#title'] = $element['#title'];
        }
      }
    }
    $newElements[$this->getElementKey($paragraph)] = $page;
    $newElements['#wrap_elements_in_last_page'] = TRUE;
    $elements = $newElements;
  }

}
