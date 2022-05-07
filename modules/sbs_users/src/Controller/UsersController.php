<?php

namespace Drupal\sbs_users\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\sbs_chat\Controller\ChatController;
use Drupal\sbs_ehelpers\SbsEhelpersQuestionnaire;
use Drupal\sbs_users\Profile;
use Drupal\sbs_questionnaire\QuestionnaireService;
use Drupal\webform_content_score\Score;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for SBS Users routes.
 */
class UsersController extends ControllerBase {

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
  protected QuestionnaireService $questionnaireService;

  /**
   * The score service.
   *
   * @var \Drupal\webform_content_score\Score
   */
  protected Score $score;

  /**
   * The E-helper questionnaire service.
   *
   * @var \Drupal\sbs_ehelpers\SbsEhelpersQuestionnaire
   */
  protected SbsEhelpersQuestionnaire $ehelperQuestionnaire;

  /**
   * The SBS profile service.
   *
   * @var \Drupal\sbs_users\Profile
   */
  protected Profile $profile;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\sbs_questionnaire\QuestionnaireService $questionnaire_service
   *   The questionnaire service.
   * @param \Drupal\webform_content_score\Score $score
   *   The score service.
   * @param \Drupal\sbs_ehelpers\SbsEhelpersQuestionnaire $ehelper_questionnaire
   *   The E-helper questionnaire service.
   * @param \Drupal\sbs_users\Profile $profile
   *   The Profile service.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The Class Resolver service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    QuestionnaireService $questionnaire_service,
    Score $score,
    SbsEhelpersQuestionnaire $ehelper_questionnaire,
    Profile $profile,
    ClassResolverInterface $class_resolver
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->questionnaireService = $questionnaire_service;
    $this->score = $score;
    $this->ehelperQuestionnaire = $ehelper_questionnaire;
    $this->profile = $profile;
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('sbs_questionnaire.service'),
      $container->get('webform_content_score.score'),
      $container->get('sbs_ehelpers.questionnaire'),
      $container->get('sbs_users.profile'),
      $container->get('class_resolver'),
    );
  }

  /**
   * Builds the response.
   */
  public function build(AccountInterface $user) {
    if (str_starts_with($user->name->value, '+')) {
      $phone = $user->name->value;
      $email = $this->t('Not given');
    }
    else {
      $email = $user->name->value;
      $phone = $this->t('Not given');
    }

    $scoredQuestionnaire = $this->questionnaireService->getScoredQuestionnaire();
    if ($scoredQuestionnaire !== FALSE && $score = $this->score->getScore($scoredQuestionnaire, $user)) {
      // Default: show the number.
      $questionnaireResult = $this->score->getScoreLevelTitle($scoredQuestionnaire, $score) ?: $score;
    }
    else {
      $questionnaireResult = $this->t('Not answered yet');
    }

    $isEhelperRequested = $this->ehelperQuestionnaire->hasUserQuestionnairesSubmitted($user);
    $answers = $isEhelperRequested ? $this->ehelperQuestionnaire->getAnswersToQuestionnaire($user) : NULL;

    $notesForm = $this->formBuilder()->getForm('Drupal\sbs_user_notes\Form\NotesForm', $user);

    $tabs = [
      'tabs' => [
        '#type' => 'horizontal_tabs',
        '#tree' => TRUE,
        '#prefix' => '<div id="user-tabs">',
        '#suffix' => '</div>',
      ],
    ];
    $tabs['tabs']['notes_tab'] = [
      '#type' => 'details',
      '#id' => 'notes',
      '#title' => $this->t('Notes'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      'notes_form' => $notesForm,
    ];
    $tabs['tabs']['progress_tabs'] = [
      '#type' => 'details',
      '#id' => 'progress',
      '#title' => $this->t('Progress'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      'progress_title' => [
        '#prefix' => '<h6>',
        '#markup' => $this->t('Current Session'),
        '#suffix' => '</h6>',
      ],
      'progress' => [
        '#markup' => $this->profile->getProgress($user),
      ],
      'activities_title' => [
        '#prefix' => '<h6>',
        '#markup' => $this->t('Activities'),
        '#suffix' => '</h6>',
      ],
      'activities' => [
        '#theme' => 'sbs_user_activities',
        '#activities' => $this->profile->getActivities($user),
      ],
    ];
    $tabs['tabs']['messages'] = $this->renderChat($user);

    $tabs['#attached']['library'][] = 'field_group/element.horizontal_tabs';

    $build = [
      '#theme' => 'sbs_user_profile',
      '#participant' => $user,
      '#email' => $email,
      '#phone' => $phone,
      '#profile_tabs' => $tabs,
      '#questionnaire_result' => $questionnaireResult,
      '#ehelpers' => $user->get('field_assigned_ehelpers')->referencedEntities(),
      '#is_ehelper_requested' => $isEhelperRequested,
      '#answers_to_ehelper_request' => $answers,
    ];

    return $build;
  }

  /**
   * Renders admin chat as a tab.
   */
  protected function renderChat($participant) {
    /* @var $controller ChatController */
    $controller = $this->classResolver->getInstanceFromDefinition(ChatController::class);
    $chat_render = $controller->adminChat($participant);

    return [
      '#type' => 'details',
      '#id' => 'sbs-tab-messages',
      '#title' => $this->t('Messages'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ] + $chat_render;
  }

  /**
   * Checks access to /user/{user}/participant page.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   User object loaded from the URL.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   Access status.
   */
  public function access(AccountInterface $user) {
    // Obviously, we can't have access to the profile of the anon user.
    if ($user->isAnonymous()) {
      return AccessResult::forbidden();
    }

    // If the current user can access any participant profile - then let them.
    if ($this->currentUser()->hasPermission('view all participant profiles')) {
      return AccessResult::allowed();
    }

    // Check if the current user can access participant profiles assigned
    // to them & they have an appropriate permission.
    /** @var \Drupal\user\UserInterface $ehelpers */
    $ehelpers = $user->get('field_assigned_ehelpers')->referencedEntities();
    $ehelper_uids = [];
    foreach ($ehelpers as $ehelper) {
      $ehelper_uids[] = $ehelper->id();
    }
    if (in_array($this->currentUser()->id(), $ehelper_uids) && $this->currentUser()->hasPermission('view own participant profiles')) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
