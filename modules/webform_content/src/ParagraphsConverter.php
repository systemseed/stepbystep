<?php

namespace Drupal\webform_content;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Process paragraps.
 */
class ParagraphsConverter {

  /**
   * The WebformContentParagraphPluginManager.
   *
   * @var \Drupal\webform_content\WebformContentParagraphPluginManager
   */
  protected $elementsPluginManager;

  /**
   * The WebformContentSettingsPluginManager.
   *
   * @var \Drupal\webform_content\WebformContentSettingsPluginManager
   */
  protected $settingsPluginManager;

  /**
   * Constructs service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\webform_content\WebformContentParagraphPluginManager $elementsPluginManager
   *   The pluginManager.
   * @param \Drupal\webform_content\WebformContentSettingsPluginManager $settingsPluginManager
   *   The pluginManager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    WebformContentParagraphPluginManager $elementsPluginManager,
    WebformContentSettingsPluginManager $settingsPluginManager
  ) {
    $this->elementsPluginManager = $elementsPluginManager;
    $this->settingsPluginManager = $settingsPluginManager;
  }

  /**
   * Process the paragraphs types to their webform representation.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $paragraphs
   *   Paragraphs from the webform node.
   * @param string $langcode
   *   Attempt to translate to this langcode.
   *
   * @return array
   *   Webform elements.
   */
  public function getWebformElements(array $paragraphs, $langcode = NULL) {
    $elements = [];
    foreach ($paragraphs as $paragraph) {
      $paragraphType = $paragraph->getType();
      // Remove 'webform_content_' prefix (16 characters).
      $pluginId = substr($paragraphType, 16);
      if (!$this->elementsPluginManager->hasDefinition($pluginId)) {
        continue;
      }
      $plugin = $this->elementsPluginManager->createInstance($pluginId);
      if ($langcode && $paragraph->hasTranslation($langcode)) {
        $paragraph = $paragraph->getTranslation($langcode);
      }
      $plugin->addElements($paragraph, $elements, $langcode);
    }
    if (isset($elements['#wrap_elements_in_last_page'])) {
      unset($elements['#wrap_elements_in_last_page']);
      $this->wrapElementsInLastPage($elements);
    }
    return $elements;
  }

  /**
   * Process the paragraphs types to their webform settings.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $paragraphs
   *   Paragraphs from the webform node.
   *
   * @return array
   *   Webform settings.
   */
  public function getWebformSettings(array $paragraphs) {
    $settings = [];
    foreach ($paragraphs as $paragraph) {
      $paragraphType = $paragraph->getType();
      // Remove 'webform_content_' prefix (16 characters).
      $pluginId = substr($paragraphType, 16);
      if (!$this->settingsPluginManager->hasDefinition($pluginId)) {
        continue;
      }
      $plugin = $this->settingsPluginManager->createInstance($pluginId);
      $plugin->addSettings($paragraph, $settings);
    }
    return $settings;
  }

  /**
   * Process dangling elements not nested in a page.
   *
   * Group all elements not currently on a page.
   *
   * @param array $elements
   *   Webform elements.
   */
  protected function wrapElementsInLastPage(array &$elements) {
    $newElements = [];
    $page = [
      '#type' => 'webform_wizard_page',
    ];
    foreach ($elements as $elementKey => $element) {
      if ($element['#type'] === 'webform_wizard_page') {
        $newElements[$elementKey] = $element;
      }
      else {
        $page[$elementKey] = $element;
      }
    }
    $newElements['webform_content_last_page'] = $page;
    $elements = $newElements;
  }

}
