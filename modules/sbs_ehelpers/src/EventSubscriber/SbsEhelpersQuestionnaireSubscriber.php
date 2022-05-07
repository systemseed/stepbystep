<?php

namespace Drupal\sbs_ehelpers\EventSubscriber;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\webform_content\Event\OverrideSettingsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * SBS questionnaire event subscriber.
 */
class SbsEhelpersQuestionnaireSubscriber implements EventSubscriberInterface {

  /**
   * The config pages loader.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   */
  protected $configPagesLoader;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $configPagesLoader
   *   The config pages loader.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(ConfigPagesLoaderServiceInterface $configPagesLoader, EntityRepositoryInterface $entity_repository) {
    $this->configPagesLoader = $configPagesLoader;
    $this->entityRepository = $entity_repository;
  }

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
    $settings = $event->getSettings();

    $questionnaire = $this->configPagesLoader->getValue('request_ehelper', 'field_ehelper_questionnaire', 0);
    // Only for the ehelper questionnaire.
    if (!$questionnaire || $event->getWebformSubmission()->get('entity_id')->value !== $questionnaire['target_id']) {
      return;
    }
    $settings['confirmation_type'] = 'url';
    // Redirect to the sessions page.
    // The system does not store the node id for the main sessions
    // page since there should be only one with this fixed path so
    // it can not be built from the entity.node.canonical route.
    $settings['confirmation_url'] = '/sessions';
    $event->setSettings($settings);
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
