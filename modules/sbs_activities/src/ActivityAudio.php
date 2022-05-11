<?php

namespace Drupal\sbs_activities;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Handler for activity audio page.
 */
class ActivityAudio {

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
   * File url generator object.
   *
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  protected FileUrlGenerator $fileUrlGenerator;

  /**
   * Creates a new instance of activity checklist handler.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager handler.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   Current user account object.
   * @param \Drupal\Core\File\FileUrlGenerator $fileUrlGenerator
   *   File url generator object.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, AccountInterface $currentUser, FileUrlGenerator $fileUrlGenerator) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

  /**
   * Return activity checklist's page data.
   *
   * @param \Drupal\node\NodeInterface $activity
   *   Checklist activity node.
   *
   * @return array
   *   Render array for the page.
   */
  public function getPageData(NodeInterface $activity): array {
    /** @var \Drupal\file\FileInterface $file */
    $file = $activity->get('field_audio')->entity;

    $data = [
      'id' => (int) $activity->id(),
      'title' => $activity->label(),
      'description' => $activity->get('field_description')->getString(),
      'audio_url' => !empty($file) ? $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()) : '',
    ];

    // You can use `jQuery('#activities').data('application')`
    // in the browser console for debug.
    $build['application'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'activities',
        'data-application' => Json::encode($data),
      ],
    ];

    if (!empty($data['audio_url'])) {
      $preload_audio = [
        '#tag' => 'link',
        '#attributes' => [
          'rel' => 'preload',
          'href' => $data['audio_url'],
          'as' => 'audio',
        ],
      ];

      $build['application']['#attached']['html_head'][] = [
        $preload_audio,
        'preload_audio',
      ];
    }

    $build['#attached'] = [
      'library' => ['sbs_activities/audio'],
    ];

    return $build;
  }

}
