<?php

namespace Drupal\anu_lms_storyline\Controller;

use Drupal\anu_lms\Settings;
use Drupal\anu_lms_storyline\Normalizer;
use Drupal\anu_lms_storyline\Storyline;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Controller\TaxonomyController;
use Drupal\taxonomy\VocabularyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for anu_lms_storyline.module.
 */
class StorylineController extends TaxonomyController {

  /**
   * The normalizer.
   *
   * @var \Drupal\anu_lms_storyline\Normalizer
   */
  protected Normalizer $normalizer;

  /**
   * The storyline service.
   *
   * @var \Drupal\anu_lms_storyline\Storyline
   */
  private Storyline $storyline;

  /**
   * Anu LMS settings.
   *
   * @var \Drupal\anu_lms\Settings
   */
  private Settings $anulmsSettings;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Creates the controller.
   */
  public function __construct(Normalizer $normalizer, Storyline $storyline, Settings $anulmsSettings, ModuleHandlerInterface $moduleHandler, ConfigFactoryInterface $configFactory) {
    $this->normalizer = $normalizer;
    $this->storyline = $storyline;
    $this->anulmsSettings = $anulmsSettings;
    $this->moduleHandler = $moduleHandler;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('anu_lms_storyline.normalizer'),
      $container->get('anu_lms_storyline.storyline'),
      $container->get('anu_lms.settings'),
      $container->get('module_handler'),
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function addForm(VocabularyInterface $taxonomy_vocabulary) {
    $term = $this->entityTypeManager()->getStorage('taxonomy_term')->create([
      'vid' => $taxonomy_vocabulary->id(),
      'field_is_storyline' => TRUE,
    ]);
    $form = $this->entityFormBuilder()->getForm($term, 'storyline');
    return $form;
  }

  /**
   * Returns a json of all available storylines.
   */
  public function viewStorylines(VocabularyInterface $taxonomy_vocabulary) {
    // Attaches general site settings.
    $data['settings'] = $this->anulmsSettings->getSettings();

    if ($this->moduleHandler->moduleExists('pwa')) {
      $data['pwa'] = $this->anulmsSettings->getPwaSettings();
    }

    $current_storyline = NULL;
    if (!$this->getUser()->field_storyline_choice->isEmpty()) {
      $current_storyline = $this->getUser()->field_storyline_choice->entity->get('tid')->get(0)->get('value')->getValue();
    }
    $data['data'] = [
      'storylines' => $this->storyline->getStorylines(),
      'currentStoryline' => $current_storyline,
    ];
    $build['application'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'anu-application',
        'data-application' => Json::encode($data),
        'data-permissions' => Json::encode($this->anulmsSettings->getPermissions()),
        'data-entity_labels' => Json::encode($this->configFactory->get('anu_lms.entity_labels')->getOriginal()),
      ],
    ];

    $build['#attached'] = [
      'library' => [
        'anu_lms_storyline/storylines_page',
        'anu_lms_storyline/vendor',
      ],
    ];

    // Disable cache for this page. @todo can be improved using cache tags.
    $build['#cache']['max-age'] = 0;

    return $build;
  }

  /**
   * Saves the selected storyline to the user.
   */
  public function selectStoryline(Request $request) {
    $storyline_id = $request->request->get('storyline-id');
    $storyline_entity = $this->storyline->getStoryline($storyline_id);

    $user = $this->getUser();

    $user->field_storyline_choice->entity = $storyline_entity;
    $user->save();

    return new RedirectResponse(Url::fromUri('internal:/sessions')->toString(), '302');
  }

  /**
   * Returns the current user entity.
   */
  protected function getUser() {
    return $this->entityTypeManager()->getStorage('user')->load($this->currentUser()->id());
  }

}
