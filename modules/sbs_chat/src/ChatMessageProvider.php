<?php

namespace Drupal\sbs_chat;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provider service for chat messages.
 */
class ChatMessageProvider {

  /**
   * Date format for display in message.
   */
  const DATE_FORMAT = 'G:i';

  /**
   * The chat message storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $chatMessageStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->chatMessageStorage = $entity_type_manager->getStorage('chat_message');
    $this->currentUser = $current_user;
  }

  /**
   * Returns list of messages for the given parameters.
   *
   * @param int $participant_id
   *   The user ID of participant of chat room.
   * @param int $last_message_timestamp
   *   The time of the beginning of the message list.
   *
   * @return array
   *   The list of chat messages.
   */
  public function getChatMessages($participant_id, $last_message_timestamp = NULL) {
    if (empty($participant_id)) {
      return [];
    }

    // Access check had to be disabled, otherwise users can't see answers.
    // Access checking for the chat in general should be done before
    // calling this method.
    $query = $this->chatMessageStorage->getQuery()
      ->condition('type', 'chat_message')
      ->condition('field_chat_message_participant', $participant_id)
      ->accessCheck(FALSE);

    if (!empty($last_message_timestamp)) {
      $query->condition('created', $last_message_timestamp, '>');
    }
    $query->sort('created', 'ASC');
    $ids = $query->execute();

    if (empty($ids)) {
      return [];
    }

    $chat_messages = $this->chatMessageStorage->loadMultiple($ids);

    // Direction of messages will be different for participant and e-helpers.
    $is_participant = $this->currentUser->id() == $participant_id;

    foreach ($chat_messages as $chat_message) {
      // Determining direction of every message.
      if ($is_participant) {
        $outgoing = $participant_id == $chat_message->getOwnerId();
        // Do not expose e-helper / coordinator name to participant.
        $sender_id = $outgoing ? $participant_id : 'e-helper';
      }
      else {
        $outgoing = $participant_id != $chat_message->getOwnerId();
        $sender_id = $chat_message->getOwner()->label();
      }

      $remote_id = $chat_message->get('field_chat_remote_id')->getString();
      $results[] = [
        'message' => [
          'id' => $remote_id ? $remote_id : $chat_message->id(),
          'content' => $chat_message->get('field_chat_message_text')->getString(),
          'direction' => $outgoing ? 'outgoing' : 'incoming',
          'senderId' => $sender_id,
          'timestamp' => $chat_message->get('created')->value * 1000,
          // If the message is available in Drupal then its status is
          // (at least) DeliveredToCloud.
          // @see https://github.com/chatscope/use-chat/blob/main/src/enums/MessageStatus.ts#L8
          'status' => 2,
        ],
        'conversationId' => $participant_id,
      ];
    }

    return $results;
  }

  /**
   * Provides saving message function.
   */
  public function saveMessage($data) {
    $message = $this->chatMessageStorage->create($data + ['bundle' => 'chat_message']);
    $message->save();
  }

}
