<?php

namespace Drupal\Tests\stepbystep\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\user\Entity\User;

// phpcs:disable DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema

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
      'pass' => $pass,
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
    // Login as 1 user as it's already assigned to the demo storyline.
    $account = User::load(1);
    $account->activate();
    $account->setPassword($this->rootUser->pass_raw);
    $account->save();
    $this->drupalLogin($account);

    $assert = $this->assertSession();
    // Check for the first session and open it.
    $assert->waitForText('Take the first step!');
    $assert->waitForElementVisible('css', 'div.MuiPaper-root:nth-child(1) > a:nth-child(1)')->click();

    // Check for the heading of the first lesson.
    $lesson_heading = $assert->waitForElementVisible('css', 'h6.MuiTypography-root');
    $this->assertNotEmpty($lesson_heading);
    $this->assertEquals($lesson_heading->getText(), 'Welcome');

    // Block 1st user at the end.
    $account->block();
    $account->save();
  }

}
