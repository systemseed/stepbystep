<?php

namespace Drupal\Tests\stepbystep\FunctionalJavascript;

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
   * Make sure the first session can be opened in browser.
   */
  public function testSessions() {
    // Login as 1st user.
    $account = User::load(1);
    $this->container->get('current_user')->setAccount($account);

    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->assertSame('text', $page->find('css', 'body')->getHtml());

    // Check for the first session and open it.
    $assert->waitForText('Take the first step!');
    $assert->waitForElementVisible('css', 'div.MuiPaper-root:nth-child(1) > a:nth-child(1)')->click();

    // Check for the heading of the first lesson.
    $lesson_heading = $assert->waitForElementVisible('css', 'h6.MuiTypography-root');
    $this->assertNotEmpty($lesson_heading);
    $this->assertEquals($lesson_heading->getText(), 'Welcome');
  }

}
