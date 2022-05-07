<?php

namespace Drupal\sbs_users\Controller;

use Drupal\user\Controller\UserController;
use Drupal\user\UserInterface;

/**
 * Controller routines for user routes.
 */
class UserViewController extends UserController {

  /**
   * Redirects users to their profile page.
   *
   * This controller assumes that it is only invoked for authenticated users.
   * This is enforced for the 'user.page' route with the '_user_is_logged_in'
   * requirement.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the profile of the currently logged in user.
   */
  public function userPage() {

    /** @var \Drupal\user\UserInterface $user */
    $user = \Drupal::routeMatch()->getParameter('user');
    if (empty($user) || !($user instanceof UserInterface)) {
      $user = $this->currentUser();
    }

    return $this->redirect('entity.user.edit_form', ['user' => $user->id()]);
  }

}
