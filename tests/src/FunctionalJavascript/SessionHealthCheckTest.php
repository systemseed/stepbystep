<?php

namespace Drupal\Tests\stepbystep\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\user\Entity\User;

/**
 * Check the sessions page with Javascript enabled.
 *
 * @group stepbystep
 */
class SessionHealthCheckTest extends WebDriverTestBase {

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
   * Make sure the first session can be opened in browser.
   */
  public function testSessions() {
    $account = User::load(1);
    $this->drupalLogin($account);

    $assert = $this->assertSession();
    $assert->waitForText('Take the first step!');
    $assert->waitForElementVisible('css', 'div.MuiPaper-root:nth-child(1) > a:nth-child(1)')->click();

    $lesson_heading = $assert->waitForElementVisible('css', 'h4.MuiTypography-root');
    $this->assertNotEmpty($lesson_heading);
    $this->assertEquals($lesson_heading->getText(), 'Kate test');
  }

}
