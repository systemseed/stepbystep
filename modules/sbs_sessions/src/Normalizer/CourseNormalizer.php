<?php

namespace Drupal\sbs_sessions\Normalizer;

use Drupal\anu_lms\Course;
use Drupal\anu_lms\CourseProgress;
use Drupal\anu_lms\Normalizer\CourseNormalizer as AnuCourseNormalizer;

/**
 * Converts Course node object structure to a JSON array structure.
 */
class CourseNormalizer extends AnuCourseNormalizer {

  /**
   * The course page service.
   *
   * @var \Drupal\anu_lms\Course
   */
  protected Course $course;

  /**
   * The course progress manager.
   *
   * @var \Drupal\anu_lms\CourseProgress
   */
  protected CourseProgress $courseProgress;

  /**
   * Constructs an object.
   *
   * @param \Drupal\anu_lms\Course $course
   *   The Course service.
   * @param \Drupal\anu_lms\CourseProgress $course_progress
   *   The Course progress handler.
   */
  public function __construct(Course $course, CourseProgress $course_progress) {
    $this->course = $course;
    $this->courseProgress = $course_progress;

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $normalized = parent::normalize($entity, $format, $context);

    $normalized['content_urls'] = $this->course->getLessonsAndQuizzesUrls($entity);

    if ($this->course->isLinearProgressEnabled($entity)) {
      $normalized['progress'] = $this->courseProgress->getCourseProgress($entity);

      // If progress is enabled, we disable all REST Entity Recursive cache!
      // @todo extract progress data in a separate data attribute and enable
      // back cache.
      if (isset($context[static::SERIALIZATION_CONTEXT_CACHEABILITY])) {
        /** @var \Drupal\Core\Cache\CacheableMetadata $cacheable_metadata */
          $cacheable_metadata = $context[static::SERIALIZATION_CONTEXT_CACHEABILITY];
          $cacheable_metadata->setCacheMaxAge(0);
        }
      }

      if (isset($context['course_page_categories']) && $this->courseProgress->isLocked($entity, $context['course_page_categories'])) {
        $normalized['locked'] = ['value' => TRUE];
      }

      return $normalized;
    }

}
