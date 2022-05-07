<?php

namespace Drupal\sbs_ehelpers\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\sbs_ehelpers\SbsEhelpersQuestionnaire;
use Drupal\sbs_questionnaire\QuestionnaireService;
use Drupal\webform_content_score\Score;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for SBS E-helpers routes.
 */
class SbsEhelpers extends ControllerBase {

  /**
   * The config pages loader.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   */
  protected $configPagesLoader;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
   * The score service.
   *
   * @var \Drupal\webform_content_score\Score
   */
  protected $score;

  /**
   * The E-helper questionnaire service.
   *
   * @var \Drupal\sbs_ehelpers\SbsEhelpersQuestionnaire
   */
  protected $ehelperQuestionnaire;

  /**
   * The controller constructor.
   *
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $configPagesLoader
   *   The config pages loader.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\sbs_questionnaire\QuestionnaireService $questionnaireService
   *   The questionnaire service.
   * @param \Drupal\webform_content_score\Score $score
   *   The score service.
   * @param \Drupal\sbs_ehelpers\SbsEhelpersQuestionnaire $ehelperQuestionnaire
   *   The E-helper questionnaire service.
   */
  public function __construct(
    ConfigPagesLoaderServiceInterface $configPagesLoader,
    AccountProxyInterface $currentUser,
    EntityTypeManagerInterface $entityTypeManager,
    QuestionnaireService $questionnaireService,
    Score $score,
    SbsEhelpersQuestionnaire $ehelperQuestionnaire
  ) {
    $this->configPagesLoader = $configPagesLoader;
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
    $this->questionnaireService = $questionnaireService;
    $this->score = $score;
    $this->ehelperQuestionnaire = $ehelperQuestionnaire;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config_pages.loader'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('sbs_questionnaire.service'),
      $container->get('webform_content_score.score'),
      $container->get('sbs_ehelpers.questionnaire')
    );
  }

  /**
   * Builds the response.
   */
  public function dismissPopup() {
    $currentUserEntity = $this->getUser();
    $currentUserEntity->field_ehelper_popup_dismissed = TRUE;
    $currentUserEntity->save();
    return new JsonResponse([
      'data' => ['result' => 'OK'],
      'method' => 'GET',
      'status' => 200,
    ]);
  }

  /**
   * Build the json response.
   */
  public function displayPopup() {
    return new JsonResponse([
      'data' => $this->isPopupDisplayed(),
      'method' => 'GET',
      'status' => 200,
    ]);
  }

  /**
   * Return wether the E-helper should be offered.
   */
  protected function isPopupDisplayed() {
    // An E-helper questionnaire must be configured.
    $ehelperQuestionnaireId = $this->ehelperQuestionnaire->getQuestionnaireId();
    if (!$ehelperQuestionnaireId) {
      return FALSE;
    }

    // Continue only when at least one level is checked.
    $levels = $this->configPagesLoader->getValue('request_ehelper', 'field_ehelper_score_levels');
    if (!$levels) {
      return FALSE;
    }

    // Continue only when user didn't dismiss the popup before.
    $currentUserEntity = $this->getUser();
    if ($currentUserEntity->field_ehelper_popup_dismissed->value) {
      return FALSE;
    }

    // Continue only when user didn't request an E-helper.
    if ($this->isQuestionnaireComplete($ehelperQuestionnaireId, $currentUserEntity)) {
      return FALSE;
    }

    return $this->isScoreWithinConfiguredLevels();
  }

  /**
   * True when the score matches.
   *
   * Calculate if the level score for the scored questionnaire is one that
   * allows to request an E-helper.
   */
  protected function isScoreWithinConfiguredLevels() {
    $currentUserEntity = $this->getUser();
    $levels = $this->configPagesLoader->getValue('request_ehelper', 'field_ehelper_score_levels');
    $scoredQuestionnaire = $this->questionnaireService->getScoredQuestionnaire();
    if ($scoredQuestionnaire === FALSE) {
      return FALSE;
    }

    $score = $this->score->getScore($scoredQuestionnaire, $currentUserEntity);
    if (!$score) {
      return FALSE;
    }

    $questionnaireResult = $this->score->getScoreLevel($scoredQuestionnaire, $score);
    if (!$questionnaireResult) {
      return FALSE;
    }

    // The scored level matches one of the levels configured, show the popup.
    foreach ($levels as $level) {
      if ($level['value'] === $questionnaireResult->id()) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Return the page for the E-helper request.
   */
  public function requestPage() {
    $ehelperQuestionnaireId = $this->ehelperQuestionnaire->getQuestionnaireId();
    if ($this->isQuestionnaireComplete($ehelperQuestionnaireId, $this->getUser())) {
      $title = $this->configPagesLoader->getValue('request_ehelper', 'field_ehelper_requested_title', 0)['value']
        ?? $this->t('Your details for E-helper');
      $text = $this->configPagesLoader->getFieldView('request_ehelper', 'field_ehelper_requested_tx_above');
      $secondText = $this->configPagesLoader->getFieldView('request_ehelper', 'field_ehelper_requested_text');
      $number = $this->ehelperQuestionnaire->getPhoneNumberFromSubmission($ehelperQuestionnaireId, $this->getUser()) ?? $this->t('No phone number given');
      $accept = NULL;
      $questionnaireUrl = NULL;
    }
    else {
      $title = $this->configPagesLoader->getValue('request_ehelper', 'field_ehelper_title', 0)['value']
        ?? $this->t('Would you like to request an E-helper?');
      $text = $this->configPagesLoader->getFieldView('request_ehelper', 'field_ehelper_text');
      $secondText = NULL;
      $number = NULL;
      $accept = $this->configPagesLoader->getValue('request_ehelper', 'field_ehelper_accept_button_text', 0)['value']
        ?? $this->t('Request E-helper');
      $questionnaire = $this->configPagesLoader->getValue('request_ehelper', 'field_ehelper_questionnaire', 0);
      $questionnaireUrl = Url::fromRoute('entity.node.canonical', ['node' => $questionnaire['target_id']])->toString();
    }

    $build['content'] = [
      '#theme' => 'sbs_ehelpers_request',
      '#title' => $title,
      '#text' => $text,
      '#second_text' => $secondText,
      '#number' => $number,
      '#accept' => $accept,
      '#questionnaire_url' => $questionnaireUrl,
    ];
    return $build;
  }

  /**
   * Remove the E-helper from the user.
   */
  public function removeEhelper($user, $ehelper) {
    // Remove the E-helper from the list of assigned.
    $ehelpers = $user->get('field_assigned_ehelpers');
    foreach ($ehelpers as $key => $assignedEhelper) {
      if ($assignedEhelper->get('target_id')->getValue() === $ehelper->id()) {
        $ehelpers->removeItem($key);
      }
    }
    if ($ehelpers->isEmpty()) {
      $user->field_user_state->value = 'unassigned';
    }
    $user->save();

    // Craft the ajax response.
    $selector = '.ehelper-remove-' . $ehelper->id();

    $url = Url::fromRoute('sbs_ehelpers.assign_ehelper', ['user' => $user->id()]);
    $link = Link::fromTextAndUrl($this->t('+ Assign E-helper'), $url);
    $linkBuild = $link->toRenderable();
    $linkBuild['#attributes'] = [
      'class' => ['use-ajax', 'ehelper-assign'],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => json_encode(['width' => 900]),
    ];
    $linkBuild['#prefix'] = '<span class="ehelper-selection">';
    $linkBuild['#suffix'] = '</span>';

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand($selector, $linkBuild));
    return $response;
  }

  /**
   * Returns the current user entity.
   */
  protected function getUser() {
    return $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
  }

  /**
   * Check for any completed submissions for the questionnaire.
   */
  protected function isQuestionnaireComplete($questionnaireId, $user) {
    return !empty($this->questionnaireService->getQuestionnaireSubmissions($questionnaireId, $user));
  }

  /**
   * Checks access for the E-helper page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function requestPageAccess(AccountInterface $account) {
    $can_request = $this->ehelperQuestionnaire->getQuestionnaireId() &&
      $this->configPagesLoader->getValue('request_ehelper', 'field_ehelper_score_levels') &&
      $this->questionnaireService->getScoredQuestionnaire() &&
      $this->isScoreWithinConfiguredLevels();
    // Allowing cases when e-helper was assigned without request,
    // help completed or e-helper was unassigned.
    $user = $this->entityTypeManager->getStorage('user')->load($account->id());
    $is_ehelper_contacted = !$user->get('field_user_state')->isEmpty() && $user->get('field_user_state')->getValue()[0]['value'] != 'registered';

    $result = AccessResult::allowedIf(
      $account->isAuthenticated() &&
      ($can_request || $is_ehelper_contacted)
    );

    // When the user submits a new scored questionnaire
    // this condition needs to be evaluated so the cache tag
    // ENTITY_TYPE_list:BUNDLE needs to be added.
    // @see https://www.drupal.org/node/3107058
    $scoredQuestionnaire = $this->questionnaireService->getScoredQuestionnaire();
    if ($scoredQuestionnaire) {
      $result->addCacheTags(['webform_submission_list:' . $scoredQuestionnaire->get('webform')->first()->get('target_id')->getValue()]);
    }
    $result->cachePerUser();
    return $result;
  }

}
