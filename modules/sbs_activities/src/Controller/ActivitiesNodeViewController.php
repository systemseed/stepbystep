<?php

namespace Drupal\sbs_activities\Controller;

use Drupal\anu_lms\AnuLmsContentTypePluginManager;
use Drupal\anu_lms\Lesson;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\anu_lms\Controller\AnulmsNodeViewController;
use Drupal\anu_lms\Settings;
use Drupal\node\NodeInterface;
use Drupal\sbs_activities\ActivityAudio;
use Drupal\sbs_activities\ActivityChecklist;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides default node view controller for activities content types.
 */
class ActivitiesNodeViewController extends AnulmsNodeViewController {

  /**
   * The Lesson handler.
   *
   * @var \Drupal\anu_lms\Lesson
   */
  protected Lesson $lesson;

  /**
   * The Courses progress service.
   *
   * @var \Drupal\core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Activity checklist page handler.
   *
   * @var \Drupal\sbs_activities\ActivityChecklist
   */
  protected ActivityChecklist $activityChecklist;

  /**
   * Activity audio page handler.
   *
   * @var \Drupal\sbs_activities\ActivityAudio
   */
  protected ActivityAudio $activityAudio;

  /**
   * Creates a NodeViewController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\anu_lms\Settings $anulmsSettings
   *   Anu LMS Settings service.
   * @param \Drupal\anu_lms\AnuLmsContentTypePluginManager $contentTypePluginManager
   *   The plugin manager.
   * @param \Drupal\anu_lms\Lesson $lesson
   *   Lesson handler.
   * @param \Drupal\sbs_activities\ActivityChecklist $activityChecklist
   *   Activity checklist page handler.
   * @param \Drupal\sbs_activities\ActivityAudio $activityAudio
   *   Activity audio page handler.
   * @param \Drupal\core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer,
    AccountInterface $current_user,
    EntityRepositoryInterface $entity_repository,
    Settings $anulmsSettings,
    AnuLmsContentTypePluginManager $contentTypePluginManager,
    Lesson $lesson,
    ActivityChecklist $activityChecklist,
    ActivityAudio $activityAudio,
    MessengerInterface $messenger
  ) {
    parent::__construct($entity_type_manager, $renderer, $current_user, $entity_repository, $anulmsSettings, $contentTypePluginManager);
    $this->lesson = $lesson;
    $this->messenger = $messenger;
    $this->activityChecklist = $activityChecklist;
    $this->activityAudio = $activityAudio;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('current_user'),
      $container->get('entity.repository'),
      $container->get('anu_lms.settings'),
      $container->get('plugin.manager.anu_lms_content_type'),
      $container->get('anu_lms.lesson'),
      $container->get('sbs_activities.checklist'),
      $container->get('sbs_activities.audio'),
      $container->get('messenger'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $node, $view_mode = 'full', $langcode = NULL) {
    /** @var \Drupal\node\NodeInterface $node */
    $node_type = $node->bundle();

    // Modify the output only for node types we're responsible for.
    if (strpos($node_type, 'activity_') !== 0) {
      return parent::view($node, $view_mode, $langcode);
    }

    if ($node_type == 'activity_checklist') {
      $build = $this->activityChecklist->getPageData($node);
    }
    elseif ($node_type == 'activity_audio') {
      $build = $this->activityAudio->getPageData($node);
    }

    // Disable cache for this page.
    $build['#cache']['max-age'] = 0;
    return $build;
  }

  /**
   * Given an activity return all the paragraphs that reference it as such.
   */
  public function getReferencingModules(NodeInterface $activity) {
    $paragraphStorage = $this->entityTypeManager->getStorage('paragraph');
    $pids = $paragraphStorage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'course_modules')
      ->condition('field_activity.target_id', [$activity->id()], 'IN')
      ->execute();
    return $paragraphStorage->loadMultiple($pids);
  }

}
