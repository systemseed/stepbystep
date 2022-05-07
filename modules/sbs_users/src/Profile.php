<?php

namespace Drupal\sbs_users;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\anu_lms\CoursesPage;
use Drupal\anu_lms\Lesson;
use Drupal\anu_lms_storyline\Storyline;
use Drupal\sbs_activities\Controller\ActivitiesNodeViewController;

/**
 * Sbs user profile related methods.
 */
class Profile {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Courses page handler.
   *
   * @var \Drupal\anu_lms\CoursesPage
   */
  protected CoursesPage $coursesPage;

  /**
   * Storyline handler.
   *
   * @var \Drupal\anu_lms_storyline\Storyline
   */
  protected Storyline $storyline;

  /**
   * Lesson handler.
   *
   * @var \Drupal\anu_lms\Lesson
   */
  protected Lesson $lesson;

  /**
   * Activities controller.
   *
   * @var \Drupal\sbs_activities\Controller\ActivitiesNodeViewController
   */
  protected ActivitiesNodeViewController $activitiesController;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $date;

  /**
   * Constructs a Profile object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\anu_lms\CoursesPage $coursesPage
   *   The courses page service.
   * @param \Drupal\anu_lms_storyline\Storyline $storyline
   *   The storyline service.
   * @param \Drupal\anu_lms\Lesson $lesson
   *   The lesson service.
   * @param \Drupal\sbs_activities\Controller\ActivitiesNodeViewController $activities_controller
   *   The activities controller.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection object.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date
   *   Date formatter.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CoursesPage $coursesPage, Storyline $storyline, Lesson $lesson, ActivitiesNodeViewController $activities_controller, Connection $database, DateFormatterInterface $date) {
    $this->entityTypeManager = $entity_type_manager;
    $this->coursesPage = $coursesPage;
    $this->storyline = $storyline;
    $this->lesson = $lesson;
    $this->activitiesController = $activities_controller;
    $this->database = $database;
    $this->date = $date;
  }

  /**
   * A string representation of the last lesson completed.
   */
  public function getProgress($user) {
    /** @var \Drupal\user\UserInterface $user */
    // Load activities from the current storyline (character).
    // The order is important.
    $storyline = $this->storyline->getUserStoryline($user);
    if (!$storyline) {
      return $this->t('No progress on any session yet');
    }
    $sessions = $this->coursesPage->getCoursesByCategories([$storyline]);

    if (!$sessions) {
      return $this->t('The character selected by the user does not have any sessions');
    }

    $sessionCount = 0;
    foreach ($sessions as $session) {
      $sessionCount++;
      $parts = $session->get('field_course_module')->referencedEntities();
      $partCount = 0;
      foreach ($parts as $part) {
        $partCount++;
        $lessons = $part->get('field_module_lessons')->referencedEntities();
        foreach ($lessons as $lesson) {
          if (!$this->lesson->isCompletedByUser($lesson, $user->id())) {
            return $this->t('@label (Session @sessionCount - Part @partCount)', [
              '@label' => $session->label(),
              '@sessionCount' => $sessionCount,
              '@partCount' => $partCount,
            ]);
          }

        }
      }
    }
    return $this->t('All sessions completed');
  }

  /**
   * A structure representing the progress on activities.
   *
   * Example:
   *   [
   *     'locked' => TRUE,
   *     'name' => 'Breathing exercise',
   *   ],
   *   [
   *     'locked' => FALSE,
   *     'name' => 'Grounding Exercise ',
   *     'unlocked_date' => '1644479189',
   *   ],
   *   [
   *     'locked' => FALSE,
   *     'name' => 'Gratitude list',
   *     'unlocked_date' => '1644479489',
   *     'items_groups' => [
   *       'Fri 21 Jan 2022' => [
   *         'Nature',
   *         'Exercise',
   *       ],
   *       'Thu 10 Feb 2022' => [
   *         'Family',
   *       ],
   *     ],
   *   ],
   */
  public function getActivities($user) {
    $storyline = $this->storyline->getUserStoryline($user);
    if (!$storyline) {
      return [];
    }
    $sessions = $this->coursesPage->getCoursesByCategories([$storyline]);
    if (!$sessions) {
      return [];
    }
    $activities = [];
    foreach ($sessions as $session) {
      $parts = $session->get('field_course_module')->referencedEntities();
      foreach ($parts as $part) {
        $activity = (int) $part->get('field_activity')->getString();
        if (!empty($activity) && !in_array($activity, $activities)) {
          $activities[] = $activity;
        }
      }
    }

    /** @var \Drupal\node\NodeInterface[] $activities */
    $activities = $this->entityTypeManager->getStorage('node')
      ->loadMultiple($activities);

    $activitiesInfo = [];

    foreach ($activities as $activity) {
      $locked = !$this->hasAccessToActivity($activity, $user->id());
      $unlockedTimestamp = $locked ? '' : $this->getUnlockedDate($activity, $user->id());

      $groups = $locked ? FALSE : $this->getGroups($activity, $user->id());
      $activitiesInfo[] = [
        'locked' => $locked,
        'name' => $activity->label(),
        'unlocked_date' => $unlockedTimestamp,
        'items_groups' => $groups,
      ];
    }
    return $activitiesInfo;
  }

  /**
   * Helper function to retrieve the date when the activity was unlocked.
   *
   * Simplifies the logic by fetching the date when the first lesson on the
   * module that contains the activity was completed.
   */
  public function getUnlockedDate($activity, $userId) {

    /** @var \Drupal\paragraphs\ParagraphInterface[] $modules */
    $modules = $this->activitiesController->getReferencingModules($activity);
    foreach ($modules as $module) {
      if ($module->get('field_module_lessons')->isEmpty()) {
        continue;
      }
      $lessons = $module->get('field_module_lessons')->referencedEntities();
      $firstLesson = reset($lessons);

      $result = $this->database->select('anu_lms_progress')
        ->fields('anu_lms_progress', ['created'])
        ->condition('nid', $firstLesson->id())
        ->condition('uid', $userId)
        ->execute()
        ->fetchObject();
      if (!$result) {
        continue;
      }
      return $result->created;

    }
    return 0;
  }

  /**
   * Helper function to retrieve checklist item grouped by day granularity.
   */
  public function getGroups($activity, $userId) {
    if ($activity->bundle() !== 'activity_checklist') {
      return FALSE;
    }
    $current_items = $this->entityTypeManager
      ->getStorage('activity_checklist_item')
      ->loadByProperties([
        'field_activity' => $activity->id(),
        'uid' => $userId,
      ]);

    $groups = [];
    foreach ($current_items as $item) {

      $created = $item->get('created')->value;
      $createdFormatted = $this->date->format($created, 'short_date');

      $groups[$createdFormatted][] = strip_tags($item->get('field_text')->getString());
    }

    return empty($groups) ? FALSE : $groups;
  }

  /**
   * Helper to determine access to a certain activity.
   */
  protected function hasAccessToActivity($activity, int $userId) {
    // Find a paragraph that references this activity and
    // has all lessons completed, disregarding the last one.
    /** @var \Drupal\paragraphs\ParagraphInterface[] $modules */
    $modules = $this->activitiesController->getReferencingModules($activity);
    foreach ($modules as $module) {
      if ($module->get('field_module_lessons')->isEmpty()) {
        continue;
      }
      $lessons = $module->get('field_module_lessons')->referencedEntities();
      array_pop($lessons);
      foreach ($lessons as $lesson) {
        // If at least one lesson in the module is not yet completed - the
        // activity can't be accessed.
        if (!$this->lesson->isCompletedByUser($lesson, $userId)) {
          return FALSE;
        }
      }

      // If at least one module has all lessons completed - the activity
      // can be accessed.
      return TRUE;
    }

    return FALSE;
  }

}
