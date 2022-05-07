<?php

namespace Drupal\sbs_user_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Login controller.
 */
class SbsLoginController extends ControllerBase {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   */
  public function __construct(AccountInterface $account) {
    $this->currentUser = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Redirect when trying to access user canonical route.
   */
  public function userViewPage() {
    if ($this->currentUser->isAuthenticated()) {
      return new RedirectResponse(Url::fromUri('internal:/sessions')->toString(), '302');
    }
    return $this->redirect('user.login');
  }

}
