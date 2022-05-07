<?php

namespace Drupal\Tests\sbs_users\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verify that the configured defaults load as intended.
 *
 * @group sbs_users
 */
class HealthCheckTestCase extends BrowserTestBase {
  /**
   * {@inheritdoc}
   */
  protected static $modules = ['sbs_users'];

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   */
  // @codingStandardsIgnoreStart
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

  /**
   * Tests that the reaction rule listing page works.
   */
  public function testOpenFrontPage() {

    $this->drupalGet('<front>');
    $this->assertUrl('/');
  }

}
