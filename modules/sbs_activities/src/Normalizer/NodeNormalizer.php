<?php

namespace Drupal\sbs_activities\Normalizer;

use Drupal\anu_lms\Normalizer\NodeNormalizerBase as AnuLmsNormalizer;
use Drupal\Core\Url;

/**
 * Converts the Drupal node object structure to a JSON array structure.
 *
 * Add upcomingActivity property when the there is an
 * activity configured as next action.
 */
class NodeNormalizer extends AnuLmsNormalizer {

  /**
   * {@inheritdoc}
   */
  protected array $supportedBundles = ['module_lesson'];

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $normalized = parent::normalize($entity, $format, $context);

    // @todo Injecting anu_lms.lesson would create a circular dependency.
    // Move this method into an event listener when anu adds an
    // event to modify lesson data.
    /** @var \Drupal\anu_lms\Lesson $lessonHandler */
    $lessonHandler = \Drupal::service('anu_lms.lesson');

    $course = $lessonHandler->getLessonCourse($entity->id());
    foreach ($course->get('field_course_module')->referencedEntities() as $courseModule) {
      if ($courseModule->get('field_activity')->isEmpty()) {
        continue;
      }
      $lessons = $courseModule->get('field_module_lessons')->getValue();
      $lesson = end($lessons);
      if ($lesson['target_id'] === $entity->id()) {
        $normalized['upcomingActivity'] = [
          'url' => Url::fromRoute('entity.node.canonical', ['node' => $courseModule->field_activity->target_id])->toString(),
        ];
        return $normalized;
      }
    }

    return $normalized;
  }

}
