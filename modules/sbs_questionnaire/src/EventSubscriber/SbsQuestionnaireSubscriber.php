<?php

namespace Drupal\sbs_questionnaire\EventSubscriber;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\webform_content\Event\AlterFormEvent;
use Drupal\webform_content\Event\OverrideSettingsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * SBS questionnaire event subscriber.
 */
class SbsQuestionnaireSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;
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
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $configPagesLoader
   *   The config pages loader.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(ConfigPagesLoaderServiceInterface $configPagesLoader, EntityRepositoryInterface $entity_repository, LanguageManagerInterface $languageManager) {
    $this->configPagesLoader = $configPagesLoader;
    $this->entityRepository = $entity_repository;
    $this->languageManager = $languageManager;
  }

  /**
   * Webform content settings event handler.
   *
   * Sets the confirmation url for the initial questionnaires
   * to go to the next one after submission.
   *
   * @param \Drupal\webform_content\Event\OverrideSettingsEvent $event
   *   Response event.
   */
  public function onSettingsOverride(OverrideSettingsEvent $event) {
    $settings = $event->getSettings();

    // HTML5 validation interferes with going to the previous question
    // in a multi-step questionnaire.
    $settings['form_novalidate'] = TRUE;

    $questionnaires = $this->configPagesLoader->getValue('sbs_questionnaires', 'field_questionnaires');
    $questionnaireIds = array_map([$this, 'mapTargetId'], $questionnaires);
    $currentWebformNodeId = $event->getWebformSubmission()->get('entity_id')->value;
    if (!in_array($currentWebformNodeId, $questionnaireIds)) {
      return;
    }

    // Webform is last in the queue.
    if (end($questionnaireIds) === $currentWebformNodeId) {
      $settings['confirmation_type'] = 'url_message';
      $settings['confirmation_url'] = Url::fromRoute(
        'webform_content_score.score_level_page',
        ['node' => $currentWebformNodeId]
      )->toString();
      $settings['confirmation_message'] = $this->t('You have successfully completed all questionnaires.');

      $event->setSettings($settings);
      return;
    }

    // Traverse array until current is found.
    $questionnaireId = reset($questionnaireIds);
    while ($questionnaireId !== $currentWebformNodeId) {
      $questionnaireId = next($questionnaireIds);
    }
    $questionnaireId = next($questionnaireIds);

    $settings['confirmation_type'] = 'url';
    $settings['confirmation_url'] = Url::fromRoute(
      'entity.node.canonical',
      ['node' => $questionnaireId]
    )->toString();

    $event->setSettings($settings);
  }

  /**
   * Webform content alter form event handler.
   *
   * @param \Drupal\webform_content\Event\AlterFormEvent $event
   *   Response event.
   */
  public function onAlterForm(AlterFormEvent $event) {
    $form = $event->getForm();

    $questionnaire = $this->entityRepository->loadEntityByUuid(
      'node',
      $event->getWebformHandler()->getConfiguration()['settings']['webform_content_node']
    );

    // Load the translated questionnaire.
    $language = $this->languageManager->getCurrentLanguage()->getId();
    if ($questionnaire->hasTranslation($language)) {
      $questionnaire = $questionnaire->getTranslation($language);
    }

    $form['questionnaire_header'] = [
      '#type' => 'container',
      '#weight' => -1,
    ];

    // Display the title of the questionnaire.
    $form['webform_content_title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#attributes' => ['class' => ['page-title']],
      '#value' => $questionnaire->label(),
      '#weight' => 0,
    ];

    // Show previous page button first.
    if (isset($form['actions']['wizard_prev'])) {
      $form['questionnaire_header']['wizard_prev'] = $form['actions']['wizard_prev'];
      $form['questionnaire_header']['#attributes']['style'] = 'justify-content: space-between';

      $form['actions']['wizard_prev']['#access'] = FALSE;
    }

    // Display next button with the same style as a submit button.
    $form['actions']['wizard_next']['#attributes']['class'][] = 'mdc-button--raised';

    $event->setForm($form);
  }

  /**
   * Build a plain array with ids.
   */
  public function mapTargetId(array $value) {
    return $value['target_id'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      OverrideSettingsEvent::EVENT_NAME => ['onSettingsOverride'],
      AlterFormEvent::EVENT_NAME => ['onAlterForm'],
    ];
  }

}
