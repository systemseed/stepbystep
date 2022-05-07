<?php

namespace Drupal\sbs_questionnaire\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Example configuration override.
 */
class ConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (in_array('field.field.paragraph.webform_content_display_text.webform_content_body', $names, TRUE)) {
      $overrides['field.field.paragraph.webform_content_display_text.webform_content_body'] = [
        'third_party_settings' => [
          'allowed_formats' => [
            'simple_html' => 'simple_html',
          ],
        ],
      ];
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'ConfigOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
