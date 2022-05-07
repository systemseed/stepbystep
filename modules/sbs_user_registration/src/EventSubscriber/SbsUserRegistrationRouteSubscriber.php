<?php

namespace Drupal\sbs_user_registration\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * SBS User Registration route subscriber.
 */
class SbsUserRegistrationRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('user.register')) {
      $route->setDefault('_title', 'Sign up');
    }
    if ($route = $collection->get('entity.user.edit_form')) {
      $defaults = $route->getDefaults();
      unset($defaults['_title_callback']);
      $defaults['_title'] = 'Profile settings';
      $route->setDefaults($defaults);
    }
    if ($route = $collection->get('entity.user.canonical')) {
      $route->addDefaults(['_controller' => '\Drupal\sbs_user_registration\Controller\SbsLoginController::userViewPage']);
    }
    if ($route = $collection->get('<front>')) {
      $route->addDefaults(['_controller' => '\Drupal\sbs_user_registration\Controller\SbsLoginController::welcomePage']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();

    // Use a lower priority than \Drupal\views\EventSubscriber\RouteSubscriber
    // to ensure the requirement will be added to its routes.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -300];

    return $events;
  }

}
