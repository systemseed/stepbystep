<?php

namespace Drupal\sbs_static_pages\Controller;

use Drupal\config_pages\ConfigPagesLoaderService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * A controller for all static pages.
 */
class SbsStaticPagesController extends ControllerBase {

  /**
   * Config pages.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderService
   */
  private ConfigPagesLoaderService $configPages;

  /**
   * The controller constructor.
   *
   * @param \Drupal\config_pages\ConfigPagesLoaderService $config_pages
   *   The Config Pages loader instance.
   */
  public function __construct(ConfigPagesLoaderService $config_pages) {
    $this->configPages = $config_pages;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config_pages.loader'));
  }

  /**
   * Builds the welcome page from a config page.
   */
  public function welcome() {
    if ($this->currentUser()->isAuthenticated()) {
      return new RedirectResponse(Url::fromUri('internal:/homepage')->toString(), '302');
    }

    /** @var \Drupal\config_pages\Entity\ConfigPages $entity */
    $entity = $this->configPages->load('homepage');

    if (!$entity) {
      return [];
    }

    return [
      '#theme' => 'sbs_welcome_page',
      '#headline' => $entity->get('field_welcome_page_headline')->value,
      '#description' => $entity->get('field_welcome_page_description')->value,
      '#sign_up_button' => $this->configPages->getFieldView('homepage', 'field_sign_up_button'),
      '#login_title' => $entity->get('field_login_title')->value,
      '#login_description' => $entity->get('field_login_description')->value,
      '#login_buttons' => $this->configPages->getFieldView('homepage', 'field_login_buttons'),
    ];
  }

  /**
   * Builds the registration consent page from a config page.
   */
  public function registrationConsent() {
    /** @var \Drupal\config_pages\Entity\ConfigPages $entity */
    $entity = $this->configPages->load('registration_consent_page');

    if (!$entity) {
      return [];
    }

    return [
      '#theme' => 'sbs_registration_consent_page',
      '#headline' => $entity->get('field_registration_consent_headl')->value,
      '#description' => $entity->get('field_registration_consent_descr')->value,
      '#terms' => $this->configPages->getFieldView('registration_consent_page', 'field_registration_terms'),
      '#consent_button' => $this->configPages->getFieldView('registration_consent_page', 'field_registration_consent_butto'),
    ];
  }

  /**
   * Builds the registration choice page from a config page.
   */
  public function registrationChoice() {
    /** @var \Drupal\config_pages\Entity\ConfigPages $entity */
    $entity = $this->configPages->load('registration_choice');

    if (!$entity) {
      return [];
    }

    return [
      '#theme' => 'sbs_registration_choice_page',
      '#headline' => $entity->get('field_registration_choice_headli')->value,
      '#description' => $entity->get('field_registration_choice_descri')->value,
      '#buttons' => $this->configPages->getFieldView('registration_choice', 'field_sign_up_buttons'),
    ];
  }

}
