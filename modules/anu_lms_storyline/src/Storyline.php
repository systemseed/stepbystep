<?php

namespace Drupal\anu_lms_storyline;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Handles retrieving storylines.
 */
class Storyline {

  /**
   * Entity type manager handler.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * ANU LMS normalizer.
   *
   * @var \Drupal\anu_lms_storyline\Normalizer
   */
  protected Normalizer $normalizer;

  /**
   * Constructs service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity repository.
   * @param \Drupal\anu_lms_storyline\Normalizer $normalizer
   *   The normalizer.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, Normalizer $normalizer) {
    $this->entityTypeManager = $entityTypeManager;
    $this->normalizer = $normalizer;
  }

  /**
   * Returns all the published storylines.
   */
  public function getStorylines() {
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $entity_query = $termStorage->getQuery();
    $entity_query->condition('field_is_storyline', TRUE);
    $entity_query->sort('weight', 'ASC');
    $result = $entity_query->accessCheck(TRUE)->execute();
    $terms = $result ? $termStorage->loadMultiple($result) : [];

    $data = [];
    foreach ($terms as $term) {
      $context = ['max_depth' => 2];
      $normalizedEntity = $this->normalizer->normalizeEntity($term, $context);
      array_push($data, $normalizedEntity);
    }
    return $data;
  }

  /**
   * Returns a storyline entity by id.
   */
  public function getStoryline($storyline_id) {
    return $this->entityTypeManager->getStorage('taxonomy_term')->load($storyline_id);
  }

  /**
   * Return storyline for the current user.
   *
   * @param \Drupal\user\UserInterface $user
   *   User object.
   *
   * @return false|int
   *   ID of user's storyline. FALSE if not selected.
   */
  public function getUserStoryline(UserInterface $user) {
    /** @var \Drupal\taxonomy\Entity\Term $character */
    $character = $user->get('field_storyline_choice')->getString();
    return !empty($character) ? (int) $character : FALSE;
  }

  /**
   * Return storyline ID for the current user within the courses page.
   *
   * @param \Drupal\node\NodeInterface $courses_page
   *   Courses page node.
   * @param \Drupal\user\UserInterface $user
   *   User object.
   *
   * @return false|int
   *   ID of user's storyline. FALSE if no applicable storyline.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getUserStorylineForCoursesPage(NodeInterface $courses_page, UserInterface $user) {
    // Anonymous users can't have personalized storyline.
    if ($user->isAnonymous()) {
      return FALSE;
    }

    /** @var \Drupal\paragraphs\ParagraphInterface[] $course_paragraphs */
    $course_paragraphs = $courses_page->get('field_courses_content')
      ->referencedEntities();

    $storyline = FALSE;
    foreach ($course_paragraphs as $course_paragraph) {
      if ($course_paragraph->bundle() == 'course_category_from_storyline') {
        $storyline = $this->getUserStoryline($user);
        if (empty($storyline) && $course_paragraph->get('field_load_default_storyline')->getString()) {
          $storyline_ids = $this->entityTypeManager->getStorage('taxonomy_term')
            ->getQuery()
            ->condition('field_is_storyline', TRUE)
            ->sort('weight', 'ASC')
            ->accessCheck(TRUE)
            ->execute();

          if (!empty($storyline_ids)) {
            // Take first character as a default.
            $storyline = (int) reset($storyline_ids);
          }
        }
      }
    }

    return $storyline;
  }

}
