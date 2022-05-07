<?php

namespace Drupal\sbs_chat\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\sbs_chat\ChatMessageProvider;
use Drupal\sbs_ehelpers\SbsEhelpersQuestionnaire;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles chat display.
 */
class ChatController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * The chat message provider.
   *
   * @var \Drupal\sbs_chat\ChatMessageProvider
   */
  protected $chatMessageProvider;

  /**
   * The E-helper questionnaire service.
   *
   * @var \Drupal\sbs_ehelpers\SbsEhelpersQuestionnaire
   */
  protected $ehelperQuestionnaire;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\sbs_chat\ChatMessageProvider $chat_message_provider
   *   The chat message provider service.
   * @param \Drupal\sbs_ehelpers\SbsEhelpersQuestionnaire $ehelper_questionnaire
   *   The E-helper questionnaire service.
   */
  public function __construct(
    AccountProxyInterface $currentUser,
    EntityTypeManagerInterface $entity_type_manager,
    ChatMessageProvider $chat_message_provider,
    SbsEhelpersQuestionnaire $ehelper_questionnaire
  ) {
    $this->currentUser = $currentUser;
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->chatMessageProvider = $chat_message_provider;
    $this->ehelperQuestionnaire = $ehelper_questionnaire;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('sbs_chat.chat_message'),
      $container->get('sbs_ehelpers.questionnaire'),
    );
  }

  /**
   * Helper method to mount chat script to the page.
   */
  protected function mountChat($conversation_id, $user_id) {
    $build = [];

    // Getting chat messages for the whole time.
    $chat_messages = $this->chatMessageProvider->getChatMessages($conversation_id);

    $data = [
      'chat' => [
        'conversationId' => $conversation_id,
        'activeUser' => $user_id,
        'messages' => $chat_messages,
      ],
    ];

    $build['application'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'chat',
        'class' => ['sbs-chat'],
      ],
    ];

    $build['#attached'] = [
      'library' => ['sbs_chat/chat', 'material_sbs/chat'],
      'drupalSettings' => $data,
    ];
    return $build;
  }

  /**
   * Returns output for the Messages page.
   *
   * @return array
   *   Render array with the content to print out.
   */
  public function participantChat() {
    return $this->mountChat($this->currentUser->id(), $this->currentUser->id());
  }

  /**
   * Returns output for the Messages tab on participant page.
   *
   * @return array
   *   Render array with the content to print out.
   */
  public function adminChat($user) {
    return $this->mountChat($user->id(), 'e-helper');
  }

  /**
   * Checks access for the Messages page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function participantChatAccess(AccountInterface $account) {
    $is_ehelper_requested = $this->ehelperQuestionnaire->hasUserQuestionnairesSubmitted($account);
    // Allowing cases when e-helper was assigned without request,
    // help completed or e-helper was unassigned.
    $user = $this->userStorage->load($account->id());
    $is_ehelper_contacted = !$user->get('field_user_state')->isEmpty() && $user->get('field_user_state')->getValue()[0]['value'] != 'registered';
    // Restricting access for anonymous and participants
    // who not requested or contacted e-helper.
    $result = AccessResult::allowedIf(
      $account->isAuthenticated() &&
      ($is_ehelper_requested || $is_ehelper_contacted)
    );

    // Invalidate cache when there are new e-helper questionnaire submissions.
    $questionnaire_id = $this->ehelperQuestionnaire->getQuestionnaireId();
    if ($questionnaire_id) {
      $questionnaire = $this->nodeStorage->load($questionnaire_id);
      $result->addCacheTags(['webform_submission_list:' . $questionnaire->get('webform')->first()->get('target_id')->getValue()]);
    }

    // Invalidate cache when user state changed.
    $result->addCacheTags(['user:' . $account->id()]);
    $result->cachePerUser();
    return $result;
  }

}
