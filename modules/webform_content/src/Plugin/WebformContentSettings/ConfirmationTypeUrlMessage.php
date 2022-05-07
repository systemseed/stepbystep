<?php

namespace Drupal\webform_content\Plugin\WebformContentSettings;

use Drupal\webform_content\WebformContentSettingsPluginBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the webform_content_settings.
 *
 * @WebformContentSettings(
 *   id = "confirmation_u_m",
 *   label = @Translation("Confirmation: Url and message"),
 *   description = @Translation("Configures a confirmation that redirects to an URL and shows a message.")
 * )
 */
class ConfirmationTypeUrlMessage extends WebformContentSettingsPluginBase {

  /**
   * {@inheritdoc}
   */
  public function addSettings(EntityInterface $paragraph, array &$settings) {
    $settings['confirmation_type'] = 'url_message';
    $url = $paragraph->webform_content_confirmation_url->first()->getUrl()->toString();
    $settings['confirmation_url'] = $url;
  }

}
