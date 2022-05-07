<?php

namespace Drupal\webform_content_score;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Helpers for calculating score levels.
 */
class Score {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Score object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Given a node with scored questions and a user return the score.
   */
  public function getScore(NodeInterface $scoredQuestionnaire, AccountInterface $user) {
    $submissionStorage = $this->entityTypeManager->getStorage('webform_submission');
    $submisions = $submissionStorage->loadByProperties([
      'webform_id' => $scoredQuestionnaire->webform->target_id,
      'uid' => $user->id(),
    ]);
    if ($submisions) {
      $submission = end($submisions);

      $score = 0;
      foreach ($submission->getData() as $elementValue) {
        if (!is_numeric($elementValue)) {
          continue;
        }
        $score += intval($elementValue);
      }

      return $score;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Get the matching score level title.
   *
   * Given a questionnaire with score levels and a score
   * return the corresponding title.
   */
  public function getScoreLevel(NodeInterface $scoredQuestionnaire, int $score) {
    foreach ($scoredQuestionnaire->questionnaire_score_levels->referencedEntities() as $scoreLevel) {
      if (
        $score < intval($scoreLevel->webform_content_score_range->from) ||
        $score > intval($scoreLevel->webform_content_score_range->to)
      ) {
        continue;
      }
      return $scoreLevel;
    }
    return FALSE;
  }

  /**
   * Get the matching score level title.
   *
   * Given a questionnaire with score levels and a score
   * return the corresponding title.
   */
  public function getScoreLevelTitle(NodeInterface $scoredQuestionnaire, int $score) {
    $scoreLevel = $this->getScoreLevel($scoredQuestionnaire, $score);
    return $scoreLevel ? $scoreLevel->webform_content_title->value : FALSE;
  }

}
