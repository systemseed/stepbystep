<?php

namespace Drupal\webform_content;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for webform_content_settings plugins.
 */
abstract class WebformContentSettingsPluginBase extends PluginBase implements WebformContentSettingsInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

}
