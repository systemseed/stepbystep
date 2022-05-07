<?php

namespace Drupal\sbs_chat\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\sbs_chat\ChatMessageProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provide endpoint for sending messages.
 *
 * @RestResource(
 *   id = "chat_send_messages",
 *   label = @Translation("Chat send messages"),
 *   uri_paths = {
 *     "create" = "chat/{conversation_id}/message"
 *   }
 * )
 */
class ChatSendingMessageResource extends ResourceBase {
  use StringTranslationTrait;

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * The chat message provider.
   *
   * @var \Drupal\sbs_chat\ChatMessageProvider
   */
  protected $chatMessageProvider;

  /**
   * Responds to POST requests.
   *
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   A current user instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\sbs_chat\ChatMessageProvider $chat_message_provider
   *   The chat message provider service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ChatMessageProvider $chat_message_provider) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->chatMessageProvider = $chat_message_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest_examples'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('sbs_chat.chat_message'),
    );
  }

  /**
   * Returns post chat messages on given params.
   */
  public function post(int $conversation_id, array $data) {
    if (!$conversation_id) {
      throw new AccessDeniedHttpException();
    }

    // Handling permissions.
    $is_coordinator = $this->currentUser->hasPermission('view all participant profiles');
    $is_ehelper = $this->currentUser->hasPermission('view own participant profiles');
    $is_participant = $this->currentUser->id() == $conversation_id;

    $has_access = FALSE;
    if ($is_coordinator || $is_participant) {
      $has_access = TRUE;
    }
    elseif ($is_ehelper) {
      $participant = $this->userStorage->load($conversation_id);
      $ehelper_ids = array_column($participant->get('field_assigned_ehelpers')->getValue(), 'target_id');
      if (in_array($this->currentUser->id(), $ehelper_ids)) {
        $has_access = TRUE;
      }
    }

    if (!$has_access) {
      throw new AccessDeniedHttpException();
    }

    // HTML is not supported (yet). Max message length is 700 symbols.
    // If you need links, please consider react-linkify first.
    // @see https://chatscope.io/storybook/react/?path=/docs/components-message--clickable-links-with-react-linkify
    $content = substr(strip_tags($data['content']), 0, 700);
    $message = [
      'type' => 'chat_message',
      'uid' => $this->currentUser->id(),
      'field_chat_message_participant' => ['target_id' => $conversation_id],
      'field_chat_message_text' => ['value' => $content],
      'field_chat_remote_id' => $data['id'],
    ];

    $this->chatMessageProvider->saveMessage($message);

    return new ResourceResponse(NULL, Response::HTTP_CREATED);
  }

}
