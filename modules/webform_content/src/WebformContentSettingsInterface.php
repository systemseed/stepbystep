<?php

namespace Drupal\webform_content;

/**
 * Interface for webform_content_settings plugins.
 */
interface WebformContentSettingsInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

}
