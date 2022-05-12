<?php

namespace Drupal\Tests\stepbystep\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Ensure Step By Step profile can be installed.
 *
 * @group stepbystep
 */
class HealthCheckTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'stepbystep';

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function drupalLogin($account, $password = '') {
    $pass = $password ? $password : $this->rootUser->pass_raw;
    $this->drupalGet(Url::fromRoute('user.login'));
    $this->submitForm([
      'name' => $account->getEmail(),
      'pass' => $this->rootUser->pass_raw,
    ], 'Log in', 'user-login-form');

    $account->sessionId = $this->getSession()->getCookie(\Drupal::service('session_configuration')->getOptions(\Drupal::request())['name']);

    $this->assertTrue($this->drupalUserIsLoggedIn($account), "User 1 is logged in.");

    $this->loggedInUser = $account;
    $this->container->get('current_user')->setAccount($account);
  }

  /**
   * Login as user 1 and ensure there is welcome page.
   */
  public function testHealthCheck() {
    $check_string = 'Take the first step!';

    $this->drupalGet('');

    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    $account = User::load(1);
    $this->drupalLogin($account);
    $contains_string = strpos( $this->getSession()->getPage()->getContent(), $check_string) !== FALSE;
    $this->assertTrue($contains_string, "Page source contains '$check_string' string");
  }

}
