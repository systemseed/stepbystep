<?php

namespace Drupal\sbs_storyline\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;

/**
 * Controller for storyline pages in sbs.
 */
class StorylineController extends ControllerBase {

  /**
   * Shows the user's current storyline.
   */
  public function viewStoryline(UserInterface $user) {
    $storyline = $user->get('field_storyline_choice')->entity;

    if (empty($storyline)) {
      return $this->redirect('anu_lms_storyline.view_storylines');
    }
    $image = $storyline->get('field_storyline_image');
    $url = $image->entity ? $image->entity->createFileUrl() : '';
    $alt = $image->alt ?: '';

    return [
      '#theme' => 'sbs_view_storyline',
      '#storyline' => [
        'name' => $storyline->get('name'),
        'description' => $storyline->get('field_storyline_description'),
        'image' => [
          'url' => $url,
          'alt' => $alt,
        ],
      ],
    ];
  }

  /**
   * Access callback for the storyline page.
   *
   * @param \Drupal\user\UserInterface $user
   *   User object from the URL.
   */
  public function access(UserInterface $user) {
    // Allow viewing / changing of only own storyline.
    if ($user->id() === $this->currentUser()->id()) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
