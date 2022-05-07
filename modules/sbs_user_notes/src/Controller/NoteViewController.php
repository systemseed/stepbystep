<?php

namespace Drupal\sbs_user_notes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller routines for note routes.
 */
class NoteViewController extends ControllerBase {

  /**
   * Current route match instance.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected CurrentRouteMatch $currentRouteMatch;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   Current route match instance.
   */
  public function __construct(CurrentRouteMatch $current_route_match) {
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match')
    );
  }

  /**
   * Redirects notes to the edit page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the note edit page.
   */
  public function notePage(): RedirectResponse {
    /** @var \Drupal\eck\EckEntityInterface $note */
    $note = $this->currentRouteMatch->getParameter('note');
    return $this->redirect('entity.note.edit_form', ['note' => $note->id()]);
  }

}
