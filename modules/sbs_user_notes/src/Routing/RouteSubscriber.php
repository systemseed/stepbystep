<?php

namespace Drupal\sbs_user_notes\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Don't provide access to the note entity page. It's not needed
    // anyway, but just in case - remember, security is important.
    if ($route = $collection->get('entity.note.canonical')) {
      $route->setDefault('_controller', '\Drupal\sbs_user_notes\Controller\NoteViewController::notePage');
    }
  }

}
