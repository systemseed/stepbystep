<?php

namespace Drupal\sbs_questionnaire;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * SBS questionnaire helper methods.
 */
class QuestionnaireService {

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
   * Constructs service.
   *
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $configPagesLoader
   *   The config pages loader.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    ConfigPagesLoaderServiceInterface $configPagesLoader,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->configPagesLoader = $configPagesLoader;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Returns the node that represents the scored questionnaire.
   */
  public function getScoredQuestionnaire() {
    $configPage = $this->configPagesLoader->load('sbs_questionnaires');
    if (!$configPage) {
      return FALSE;
    }
    $questionnaires = $configPage->get('field_questionnaires')->referencedEntities();
    return end($questionnaires);
  }

  /**
   * Get the submissions a user has for a questionnaire.
   */
  public function getQuestionnaireSubmissions($questionnaireId, $user) {
    $questionnaire = $this->entityTypeManager->getStorage('node')->load($questionnaireId);
    if (!$questionnaire) {
      return FALSE;
    }
    $webformId = $questionnaire->webform->target_id;
    $submissions = $this
      ->entityTypeManager
      ->getStorage('webform_submission')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', $user->id())
      ->condition('completed', 0, '>')
      ->condition('webform_id', $webformId)
      ->execute();

    return $submissions;
  }

}
