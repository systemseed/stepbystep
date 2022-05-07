<?php

namespace Drupal\sbs_replicate\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\replicate\Events\ReplicateEntityFieldEvent;
use Drupal\replicate\Events\ReplicatorEvents;
use Drupal\replicate\Replicator;

/**
 * SBS replicate event subscriber.
 */
class SbsReplicateSubscriber implements EventSubscriberInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The replicator.
   *
   * @var \Drupal\replicate\Replicator
   */
  protected $replicator;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\replicate\Replicator $replicator
   *   The replicator.
   */
  public function __construct(MessengerInterface $messenger, Replicator $replicator) {
    $this->messenger = $messenger;
    $this->replicator = $replicator;
  }

  /**
   * Duplicate lessons when replicated.
   *
   * @param \Drupal\Replicate\Events\ReplicateEntityFieldEvent $event
   *   The event we're working on.
   */
  public function onFieldClone(ReplicateEntityFieldEvent $event) {
    $field_item_list = $event->getFieldItemList();
    if ($field_item_list->getFieldDefinition()->getName() !== 'field_module_lessons') {
      return;
    }

    foreach ($field_item_list as $field_item) {
      $replicated_item = $this->replicator->cloneEntity($field_item->entity);
      $replicated_item->title = $replicated_item->title->value . '_clone';
      $replicated_item->save();
      $field_item->entity = $replicated_item;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ReplicatorEvents::replicateEntityField('entity_reference') => ['onFieldClone'],
    ];
  }

}
