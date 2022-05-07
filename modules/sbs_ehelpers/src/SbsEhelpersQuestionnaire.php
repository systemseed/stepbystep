<?php

namespace Drupal\sbs_ehelpers;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\sbs_questionnaire\QuestionnaireService;

/**
 * Methods related to the E-helper questionnaire.
 */
class SbsEhelpersQuestionnaire {

  /**
   * The config pages loader.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   */
  protected $configPagesLoader;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The questionnaire service.
   *
   * @var \Drupal\sbs_questionnaire\QuestionnaireService
   */
  protected $questionnaireService;

  /**
   * The controller constructor.
   *
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $configPagesLoader
   *   The config pages loader.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\sbs_questionnaire\QuestionnaireService $questionnaireService
   *   The questionnaire service.
   */
  public function __construct(
    ConfigPagesLoaderServiceInterface $configPagesLoader,
    EntityTypeManagerInterface $entityTypeManager,
    QuestionnaireService $questionnaireService
  ) {
    $this->configPagesLoader = $configPagesLoader;
    $this->entityTypeManager = $entityTypeManager;
    $this->questionnaireService = $questionnaireService;
  }

  /**
   * Get the id of the configured questionnaire to request an E-helper.
   */
  public function getQuestionnaireId() {
    $ehelperQuestionnaire = $this->configPagesLoader->getValue('request_ehelper', 'field_ehelper_questionnaire', 0);
    if (!isset($ehelperQuestionnaire['target_id'])) {
      return FALSE;
    }
    $ehelperQuestionnaireId = $ehelperQuestionnaire['target_id'];
    return $ehelperQuestionnaireId;
  }

  /**
   * Wether the user has requested an E-helper.
   */
  public function hasUserQuestionnairesSubmitted($user) {
    return !empty($this->questionnaireService->getQuestionnaireSubmissions($this->getQuestionnaireId(), $user));
  }

  /**
   * Extract the phone number from the last E-helper questionnaire submission.
   *
   * The first textfield with a "Phone" in its title is assumed to
   * be the phone number.
   */
  public function getPhoneNumberFromSubmission($questionnaireId, $user) {
    $lastSubmission = $this->getLastSubmission($questionnaireId, $user);
    $submission = $this->entityTypeManager->getStorage('webform_submission')->load($lastSubmission);
    $webform = $submission->getWebform();
    $elements = $webform->getElementsDecodedAndFlattened();
    foreach ($elements as $elementKey => $element) {
      if ($element['#type'] === 'textfield' && preg_match('/phone/i', $element['#title'])) {
        return $submission->getElementData($elementKey);
      }
    }
  }

  /**
   * Get the render array for the submission data.
   */
  public function getAnswersToQuestionnaire($user) {
    $lastSubmission = $this->getLastSubmission($this->getQuestionnaireId(), $user);
    $submission = $this->entityTypeManager->getStorage('webform_submission')->load($lastSubmission);
    $webform = $submission->getWebform();
    $elements = $webform->getElementsInitializedFlattenedAndHasValue();
    return $this->entityTypeManager->getViewBuilder('webform_submission')->buildElements($elements, $submission);
  }

  /**
   * Get the last submission a user has for a questionnaire.
   */
  protected function getLastSubmission($questionnaireId, $user) {
    $lastSubmission = $this->questionnaireService->getQuestionnaireSubmissions($questionnaireId, $user);
    return is_array($lastSubmission) ? end($lastSubmission) : FALSE;
  }

}
