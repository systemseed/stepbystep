<?php

namespace Drupal\anu_lms_storyline\EventSubscriber;

use Drupal\anu_lms_storyline\Storyline;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\anu_lms\Event\CoursesPageDataGeneratedEvent;
use Drupal\anu_lms\Normalizer;
use Drupal\anu_lms\CoursesPage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Fill data with courses from storyline.
 */
class CoursesPageFromStoryline implements EventSubscriberInterface {

  /**
   * Entity type manager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The normalizer.
   *
   * @var \Drupal\anu_lms\Normalizer
   */
  protected Normalizer $normalizer;

  /**
   * The courses page service.
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
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\anu_lms\Normalizer $normalizer
   *   The normalizer.
   * @param \Drupal\anu_lms\CoursesPage $coursesPage
   *   The courses page service.
   * @param \Drupal\anu_lms_storyline\Storyline $storyline
   *   Storyline handler.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser, Normalizer $normalizer, CoursesPage $coursesPage, Storyline $storyline) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->normalizer = $normalizer;
    $this->coursesPage = $coursesPage;
    $this->storyline = $storyline;
  }

  /**
   * Event handler.
   *
   * @param \Drupal\anu_lms\Event\CoursesPageDataGeneratedEvent $event
   *   Response event.
   */
  public function onDataGenerated(CoursesPageDataGeneratedEvent $event) {
    $data = $event->getPageData();
    /** @var \Drupal\node\NodeInterface $courses_page */
    $courses_page = $event->getNode();
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    /** @var int|FALSE $storyline_id */
    $storyline_id = $this->storyline->getUserStorylineForCoursesPage($courses_page, $user);

    $courses = [];
    if (!empty($storyline_id)) {
      $storyline = $this->entityTypeManager->getStorage('taxonomy_term')->load($storyline_id);
      $this->addStorylineAsSection($storyline, $data['courses_page']);
      $courses = $this->coursesPage->getCoursesByCategories([$storyline_id]);
    }

    $normalized_courses = [];
    foreach ($courses as $course) {
      $normalized_course = $this->normalizer->normalizeEntity($course, [
        'max_depth' => 2,
        // Pass the categories requested as context so additional logic
        // can be performed like the course being part of a sequence within
        // a category.
        // @see \Drupal\anu_lms\CourseProgress
        'course_page_categories' => $this->entityTypeManager->getStorage('taxonomy_term')
          ->loadMultiple([$storyline_id]),
      ]);
      if (!empty($normalized_course)) {
        $normalized_courses[] = $normalized_course;
      }
    }

    $data['courses'] = array_merge($data['courses'], $normalized_courses);
    $event->setPageData($data);
  }

  /**
   * Add the normalized storyline as a section.
   */
  protected function addStorylineAsSection($storyline, &$normalized_node) {
    $normalized_node['field_courses_content'][] = [
      'field_course_category' => [$this->normalizer->normalizeEntity($storyline, ['max_depth' => 3])],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'anu_lms_courses_page_data_generated' => ['onDataGenerated'],
    ];
  }

}
