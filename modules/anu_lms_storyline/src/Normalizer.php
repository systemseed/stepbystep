<?php

namespace Drupal\anu_lms_storyline;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Normalizer service for taxonomy_terms.
 */
class Normalizer {

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * Constructs service.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, Serializer $serializer) {
    $this->entityRepository = $entity_repository;
    $this->serializer = $serializer;
  }

  /**
   * Normalizes given entities with `json_recursive` normalizer.
   *
   * Load nested entities recursively with configuration from context variable.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An array of entities.
   * @param array $context
   *   An array with settings for normalizer.
   *
   * @return array
   *   An array of normalized entities.
   */
  public function normalizeEntity(EntityInterface $entity, array $context = []) {
    // Default configurations.
    $default_context = [
      'settings' => [
        'taxonomy_term' => [
          'exclude_fields' => [
            'revision_created', 'revision_user', 'revision_log_message', 'revision_translation_affected', 'path', 'default_langcode', 'langcode', 'content_translation_source', 'content_translation_outdated', 'content_translation_uid', 'content_translation_created',
            'entity_type', 'entity_bundle', 'revision_id', 'vid', 'parent', 'changed', 'field_enable_course_sequence', 'field_is_storyline',
          ],
        ],
      ],
    ];
    // Merge default and given context settings.
    $context = array_merge_recursive($default_context, $context);

    $normalized_entity = NULL;
    // Double check entity access.
    if ($entity->access('view')) {
      // Get translated version of the entity.
      $entity = $this->entityRepository->getTranslationFromContext($entity);

      // Normalize recursively given entity.
      $normalized_entity = $this->serializer->normalize($entity, 'json_recursive', $context);
    }

    return $normalized_entity;
  }

}
