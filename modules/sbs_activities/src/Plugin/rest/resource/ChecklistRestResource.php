<?php

namespace Drupal\sbs_activities\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a resource to save checklist data from an activity.
 *
 * @RestResource(
 *   id = "sbs_activities_checklist",
 *   label = @Translation("Activities: Checklist save"),
 *   uri_paths = {
 *     "create" = "/activities/checklist/{activity}"
 *   }
 * )
 */
class ChecklistRestResource extends ResourceBase {

  /**
   * Entity type manager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager instance.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
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
      $container->get('logger.factory')->get('sbs_activities'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
    );
  }

  /**
   * Saves checklist from a user.
   *
   * @param int $activity
   *   NID of activity checklist is being added to.
   * @param array $data
   *   List of checklist items added by the user.
   *   Example: [
   *     0 => [
   *       // NULL means item wasn't saved into Drupal yet.
   *       "id" => NULL,
   *       // Internal ID is how frontend merges item together.
   *       "internalId" => 3,
   *       // Whether the checklist item is completed or not.
   *       "isCompleted" => FALSE,
   *       // Checklist item name.
   *       "text" => "Do something fun today",
   *     ],
   *   ],.
   */
  public function post(int $activity, array $data): ModifiedResourceResponse {
    $saved_items = [];

    try {
      // Load & validate activity being completed.
      /** @var \Drupal\node\NodeInterface $activity_node */
      $activity_node = $this->entityTypeManager->getStorage('node')
        ->load($activity);

      // Make sure the loaded activity actually exist and is of the expected
      // node type.
      if (empty($activity_node) || $activity_node->bundle() != 'activity_checklist') {
        throw new BadRequestHttpException('Provided activity can not be found');
      }

      // Make sure the current user is actually capable of accessing the
      // activity.
      if (!$activity_node->access('view')) {
        throw new AccessDeniedHttpException('Not sufficient permissions to access the activity');
      }

      // Make sure the received payload is of the expected data type.
      if (!is_array($data)) {
        throw new BadRequestHttpException('Data is missing or has invalid format. Must be an array');
      }

      // Load currently saved checklist items in the backend keyed by entity id.
      $checklist_storage = $this->entityTypeManager->getStorage('activity_checklist_item');
      $current_items = $checklist_storage->loadByProperties([
        'field_activity' => $activity_node->id(),
        'uid' => $this->currentUser->id(),
      ]);

      foreach ($data as $item) {
        // Validate each checklist item to make sure all required fields are
        // in place.
        if (!array_key_exists('id', $item) || !isset($item['isCompleted']) || !isset($item['text'])) {
          throw new BadRequestHttpException('Checklist item does not contain required params');
        }

        // If item ID was passed in the payload, it means that the item already
        // exists in Drupal's backend, and we need to update it.
        if (!empty($item['id'])) {
          $checklist_item = $checklist_storage->load($item['id']);
          if (empty($checklist_item)) {
            throw new BadRequestHttpException(sprintf('Checklist item %s can not be loaded', $item['id']));
          }

          if ($checklist_item->get('uid')->getString() != $this->currentUser->id()) {
            throw new BadRequestHttpException(sprintf('Checklist item %s is authored by another user', $item['id']));
          }

          if ($checklist_item->get('field_activity')->getString() != $activity_node->id()) {
            throw new BadRequestHttpException(sprintf('Checklist item %s belongs to another activity', $item['id']));
          }

          // Each item found in Drupal & already updated we remove from the
          // list. Later down the road we will delete all remaining items
          // from the backend, because they were removed by the user.
          unset($current_items[$checklist_item->id()]);
        }
        else {
          // Create a new checklist item.
          $checklist_item = $checklist_storage->create([
            'type' => 'activity_checklist_item',
            'field_activity' => $activity_node,
            'uid' => $this->currentUser->id(),
          ]);
        }

        // Set checklist fields & finally save it here.
        // Title can't store more than 255 chars, so cut the text if it happens.
        $title = strlen($item['text']) > 255 ? substr($item['text'], 0, 252) . '...' : $item['text'];
        $checklist_item->set('title', $title);
        $checklist_item->set('field_text', $item['text']);
        $checklist_item->set('field_status', (bool) $item['isCompleted']);
        $checklist_item->save();

        // Make sure that item has ID (it may not be the case if the item
        // didn't exist before).
        $item['id'] = $checklist_item->id();
        $saved_items[] = $item;
      }

      // If at this point there are some items left which exist in Drupal
      // backend, but they were not referenced by the frontend, it means that
      // these items don't exist anymore.
      if (!empty($current_items)) {
        $checklist_storage->delete($current_items);
      }
    }
    catch (HttpException $exception) {
      $this->logger->error('Error on checklist submission: @message. Activity: @activity. Payload: @payload.', [
        '@message' => $exception->getMessage(),
        '@activity' => $activity,
        '@payload' => print_r($data, 1),
      ]);

      // Pass on the exception.
      throw new $exception();
    }
    catch (\Throwable $exception) {
      $this->logger->error('Exception on checklist submission: @message. Activity: @activity. Payload: @payload. Trace: @trace.', [
        '@message' => $exception->getMessage(),
        '@activity' => $activity,
        '@payload' => print_r($data, 1),
        '@trace' => $exception->getTraceAsString(),
      ]);

      throw new BadRequestHttpException('Unexpected error during checklist submission.');
    }

    return new ModifiedResourceResponse($saved_items);
  }

}
