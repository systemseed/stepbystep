<?php

namespace Drupal\webform_content;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Base class for webform_content_paragraph plugins.
 */
abstract class WebformContentParagraphPluginBase extends PluginBase implements WebformContentParagraphInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementKey(EntityInterface $entity) {
    return preg_replace('/[^a-z0-9_]+/', '_', $entity->uuid());
  }

}
