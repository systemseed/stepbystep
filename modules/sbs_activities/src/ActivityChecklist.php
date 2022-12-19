<?php

namespace Drupal\sbs_activities;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\anu_lms\Settings;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Handler for activity checklist page.
 */
class ActivityChecklist {

  /**
   * Entity type manager handler.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Current user account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * Anu LMS settings.
   *
   * @var \Drupal\anu_lms\Settings
   */
  private Settings $anulmsSettings;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Creates a new instance of activity checklist handler.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager handler.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   Current user account object.
   * @param \Drupal\anu_lms\Settings $anulmsSettings
   *   Anu LMS Settings service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, AccountInterface $currentUser, Settings $anulmsSettings, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->anulmsSettings = $anulmsSettings;
    $this->configFactory = $configFactory;
  }

  /**
   * Return activity checklist's page data.
   *
   * @param \Drupal\node\NodeInterface $activity
   *   Checklist activity node.
   *
   * @return array
   *   Render array for the page.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getPageData(NodeInterface $activity): array {
    // Load currently saved checklist items in the backend keyed by entity id.
    /** @var \Drupal\Core\Entity\EntityInterface $current_items */
    $current_items = $this->entityTypeManager
      ->getStorage('activity_checklist_item')
      ->loadByProperties([
        'field_activity' => $activity->id(),
        'uid' => $this->currentUser->id(),
      ]);

    $items = [];
    foreach ($current_items as $item) {
      $items[] = [
        'id' => $item->id(),
        'isCompleted' => (bool) $item->get('field_status')->getString(),
        'text' => $item->get('field_text')->getString(),
      ];
    }

    $suggested_items = [];
    foreach ($activity->get('field_suggestions')->getValue() as $item) {
      if (!empty($item['value'])) {
        $suggested_items[] = trim($item['value']);
      }
    }

    $data = [
      'id' => (int) $activity->id(),
      'title' => $activity->label(),
      'description' => $activity->get('field_description')->getString(),
      'items' => $items,
      'suggestedItems' => $suggested_items,
      'suggestionsLabel' => $activity->get('field_suggestions_label')->getString(),
    ];

    // You can use `jQuery('#activities').data('application')`
    // in the browser console for debug.
    $build['application'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'activities',
        'data-application' => Json::encode($data),
        'data-permissions' => Json::encode($this->anulmsSettings->getPermissions()),
        'data-entity_labels' => Json::encode($this->configFactory->get('anu_lms.entity_labels')->getOriginal()),
      ],
    ];

    $build['#attached'] = [
      'library' => ['sbs_activities/checklist'],
    ];

    return $build;
  }

}
