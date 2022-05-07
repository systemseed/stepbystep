<?php

namespace Drupal\sbs_user_registration\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Url;
use Drupal\user\UserDataInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Controller\UserController;

/**
 * Controller routines for user routes.
 */
class SbsUserController extends UserController {

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * Constructs a UserController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time service.
   */
  public function __construct(DateFormatterInterface $date_formatter, UserStorageInterface $user_storage, UserDataInterface $user_data, LoggerInterface $logger, FloodInterface $flood = NULL, TimeInterface $time) {
    parent::__construct($date_formatter, $user_storage, $user_data, $logger, $flood);
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('user.data'),
      $container->get('logger.factory')->get('user'),
      $container->get('flood'),
      $container->get('datetime.time'),
    );
  }

  /**
   * Validates user, hash, and timestamp; logs the user in if correct.
   *
   * @param int $uid
   *   User ID of the user requesting reset.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the user edit form if the information is correct.
   *   If the information is incorrect redirects to 'user.pass' route with a
   *   message for the user.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If $uid is for a blocked user or invalid user ID.
   */
  public function resetPassLogin($uid, $timestamp, $hash, Request $request) {
    // The current user is not logged in, so check the parameters.
    $current = $this->time->getRequestTime();
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($uid);

    // Verify that the user exists.
    if ($user === NULL) {
      // Invalid user ID, so deny access. The parameters will be in
      // the watchdog's URL for the administrator to check.
      throw new AccessDeniedHttpException();
    }

    // Time out, in seconds, until login URL expires.
    $timeout = $this->config('user.settings')->get('password_reset_timeout');
    // No time out for first time login.
    if ($user->getLastLoginTime() && $current - $timestamp > $timeout) {
      $this->messenger()->addError($this->t('You have tried to use a one-time login link that has expired. Please request a new one using the form below.'));
      return $this->redirect('user.pass');
    }
    elseif ($user->isAuthenticated() && ($timestamp >= $user->getLastLoginTime()) && ($timestamp <= $current) && hash_equals($hash, user_pass_rehash($user, $timestamp))) {
      user_login_finalize($user);
      $this->logger->notice('User %name used one-time login link at time %timestamp.', [
        '%name' => $user->getDisplayName(),
        '%timestamp' => $timestamp,
      ]);
      $this->messenger()->addStatus($this->t('You have just used your one-time login link. It is no longer necessary to use this link to log in.'));
      // Clear any flood events for this user.
      $this->flood->clear('user.password_request_user', $uid);
      $user->status = 1;
      $user->save();
      return new RedirectResponse(Url::fromUri('internal:/sessions')->toString(), '302');
    }

    $this->messenger()->addError($this->t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'));
    return $this->redirect('user.pass');
  }

}
