<?php

namespace Drupal\sbs_sessions\Normalizer;

use Drupal\anu_lms\Normalizer\CourseNormalizer as AnuCourseNormalizer;

/**
 * Converts Course node object structure to a JSON array structure.
 */
class CourseNormalizer extends AnuCourseNormalizer {
    /**
     * {@inheritdoc}
     */
    public function normalize($entity, $format = NULL, array $context = []) {
        $normalized = parent::normalize($entity, $format, $context);

        $courseProgressHandler = \Drupal::service('anu_lms.course_progress');
        $courseHandler = \Drupal::service('anu_lms.course');

        $normalized['content_urls'] = $courseHandler->getLessonsAndQuizzesUrls($entity);

        if ($courseHandler->isLinearProgressEnabled($entity)) {
            $normalized['progress'] = $courseProgressHandler->getCourseProgress($entity);

            // If progress is enabled, we disable all REST Entity Recursive cache!
            // @todo extract progress data in a separate data attribute and enable
            // back cache.
            if (isset($context[static::SERIALIZATION_CONTEXT_CACHEABILITY])) {
                /** @var \Drupal\Core\Cache\CacheableMetadata $cacheable_metadata */
                $cacheable_metadata = $context[static::SERIALIZATION_CONTEXT_CACHEABILITY];
                $cacheable_metadata->setCacheMaxAge(0);
            }
        }

        if (isset($context['course_page_categories']) &&
            $courseProgressHandler->isLocked($entity, $context['course_page_categories'])
        ) {
            $normalized['locked'] = ['value' => TRUE];
        }

        return $normalized;
    }

}
