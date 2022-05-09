<?php

namespace Drupal\sbs_ehelpers\EventSubscriber;

use Drupal\webform_content\Event\OverrideSettingsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * SBS questionnaire event subscriber.
 */
class SbsOnLessonProgressSubscriber implements EventSubscriberInterface {

  /**
   * Webform content settings event handler.
   *
   * Sets the confirmation url for the E-helper questionnaire
   * to go back to the sessions.
   *
   * @param \Drupal\webform_content\Event\OverrideSettingsEvent $event
   *   Response event.
   */
  public function onSettingsOverride(OverrideSettingsEvent $event) {

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      OverrideSettingsEvent::EVENT_NAME => ['onSettingsOverride'],
    ];
  }

}
