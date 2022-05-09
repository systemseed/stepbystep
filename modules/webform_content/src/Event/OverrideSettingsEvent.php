<?php

namespace Drupal\webform_content\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Plugin\WebformHandlerInterface;

/**
 * Event that is fired when the CoursesPage service generates data.
 */
class OverrideSettingsEvent extends Event {

  const EVENT_NAME = 'webform_content_override_settings';

  /**
   * The webform settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * The webform submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmission;

  /**
   * The webform handler.
   *
   * @var \Drupal\webform\Plugin\WebformHandlerInterface
   */
  protected $webformHandler;

  /**
   * Constructs the object.
   *
   * @param array $settings
   *   The webform settings..
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The webform submission.
   * @param \Drupal\webform\Plugin\WebformHandlerInterface $webformHandler
   *   The webform handler.
   */
  public function __construct(array $settings, WebformSubmissionInterface $webformSubmission, WebformHandlerInterface $webformHandler) {
    $this->settings = $settings;
    $this->webformSubmission = $webformSubmission;
    $this->webformHandler = $webformHandler;
  }

  /**
   * Get the settings.
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * Set the setttings.
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
  }

  /**
   * Get the webform submission.
   */
  public function getWebformSubmission() {
    return $this->webformSubmission;
  }

  /**
   * Get the webform handler.
   */
  public function getWebformHandler() {
    return $this->webformHandler;
  }

}
