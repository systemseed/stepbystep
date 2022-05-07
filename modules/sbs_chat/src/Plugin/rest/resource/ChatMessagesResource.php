<?php

namespace Drupal\sbs_chat\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\sbs_chat\ChatMessageProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a resource for chat messages for the given room and period.
 *
 * @RestResource(
 *   id = "chat_messages",
 *   label = @Translation("Chat messages"),
 *   uri_paths = {
 *     "canonical" = "/chat/{conversation_id}"
 *   }
 * )
 */
class ChatMessagesResource extends ResourceBase {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The current user.
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
   *   Current user object.
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
      $container->get('logger.factory')->get('rest'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('sbs_chat.chat_message')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns requested chat messages depending on given params.
   *
   * GET params (additionally to $conversation_id):
   *  - last_message_time (required, timestamp) - timestamp of the last received
   *    message on the frontend,
   *
   * Examples:
   *
   * @code
   * // Will return all messages newer than 1599716339 timestamp:
   * /chat/74?last_message_time=1599716339
   *
   * @endcode
   *
   * @param int $conversation_id
   *   The chat room ID which always matches user ID of participant.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response containing requested data.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the chat room was not found.
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when no chat room or timestamp were provided.
   */
  public function get($conversation_id, Request $request) {
    // Handling arguments.
    if (empty($conversation_id)) {
      throw new BadRequestHttpException('Missed parameters in request.');
    }
    $last_message_frontend = $request->query->get('last_message_time');
    if (empty($last_message_frontend)) {
      throw new BadRequestHttpException('Missed parameters in request.');
    }
    if (!is_numeric($last_message_frontend)) {
      throw new NotFoundHttpException("Wrong data requested.");
    }

    // Handling permissions.
    $is_coordinator = $this->currentUser->hasPermission('view all participant profiles');
    $is_ehelper = $this->currentUser->hasPermission('view own participant profiles');
    $is_participant = $this->currentUser->id() == $conversation_id;

    // Checking access.
    $can_see_chat = FALSE;
    // Bypassing coordinators and participant.
    if ($is_coordinator || $is_participant) {
      $can_see_chat = TRUE;
    }
    // Checking assignments for e-helper.
    if (!$can_see_chat && $is_ehelper) {
      $participant = $this->userStorage->load($conversation_id);
      $ehelper_ids = array_column($participant->get('field_assigned_ehelpers')->getValue(), 'target_id');

      if (in_array($this->currentUser->id(), $ehelper_ids)) {
        $can_see_chat = TRUE;
      }
    }
    if (!$can_see_chat) {
      throw new NotFoundHttpException("Wrong data requested.");
    }

    // Getting chat messages since last received message.
    $result['chat_messages'] = $this->chatMessageProvider->getChatMessages($conversation_id, $last_message_frontend);

    // Cache is disabled.
    return new ModifiedResourceResponse($result);
  }

}
