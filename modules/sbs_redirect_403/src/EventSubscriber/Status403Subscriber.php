<?php

namespace Drupal\sbs_redirect_403\EventSubscriber;

use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Event subscriber to redirect 403 to welcome page.
 */
class Status403Subscriber extends HttpExceptionSubscriberBase {

  /**
   * Reference to the current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private AccountInterface $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Redirects on 403 Access Denied kernel exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The Event to process.
   */
  public function on403(ExceptionEvent $event) {
    if ($this->currentUser->isAuthenticated()) {
      return;
    }
    $response = new CacheableRedirectResponse('/');
    $event->setResponse($response);
  }

}
