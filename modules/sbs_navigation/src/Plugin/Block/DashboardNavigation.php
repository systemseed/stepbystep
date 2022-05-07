<?php

namespace Drupal\sbs_navigation\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a navigation for Coordinator / E-helper dashboards.
 *
 * @Block(
 *   id = "sbs_navigation_dashboards",
 *   admin_label = @Translation("Coordinator / E-helper dashboard navigation"),
 *   category = @Translation("SBS navigation")
 * )
 */
class DashboardNavigation extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $links = [];
    $this->addLink($links, 'internal:/sessions', $this->t('Back to sessions'));
    $this->addLink($links, 'view.unassigned_users.page_1', $this->t('Dashboard'));
    $this->addLink($links, 'view.e_helper_management.page_1', $this->t('Team Members'));
    $this->addLink($links, 'user.logout', $this->t('Logout'));

    return [
      '#theme' => 'sbs_action_links',
      '#links' => $links,
      '#cache' => [
        'contexts' => ['user.roles'],
      ],
    ];
  }

  /**
   * Internal helper to add links to the navigation.
   */
  protected function addLink(&$links, $route_name, $link_title) {
    if (str_starts_with($route_name, 'internal:')) {
      $route = Url::fromUri($route_name);
    }
    else {
      $route = Url::fromRoute($route_name);
    }
    if (!empty($route) && $route->access()) {
      $links[] = [
        'url' => $route->toString(),
        'title' => $link_title,
      ];
    }
  }

}
