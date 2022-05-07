<?php

namespace Drupal\sbs_activities\Controller;

use Drupal\anu_lms\CoursesPage;
use Drupal\anu_lms_storyline\Storyline;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles toolbox page display.
 */
class ToolboxController extends ControllerBase {

  /**
   * Courses page handler.
   *
   * @var \Drupal\anu_lms\CoursesPage
   */
  protected CoursesPage $coursesPage;

  /**
   * Storyline handler.
   *
   * @var \Drupal\anu_lms_storyline\Storyline
   */
  protected Storyline $storyline;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new instance of this class.
   */
  public function __construct(CoursesPage $coursesPage, Storyline $storyline, EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->coursesPage = $coursesPage;
    $this->storyline = $storyline;
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('anu_lms.courses_page'),
      $container->get('anu_lms_storyline.storyline'),
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
    );
  }

  /**
   * Returns output for the toolbox page.
   *
   * @return array
   *   Render array with the content to print out.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function viewToolbox() {
    $build = [];

    $build['header'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['header'],
      ],
    ];

    $build['header']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#value' => $this->t('Toolbox'),
    ];

    $build['header']['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('Here you can practice the activities you have unlocked on your Step-by-Step journey'),
    ];

    $activities = _sbs_get_current_user_activities();

    $data = ['activities' => []];
    $toolbox_url = Url::fromRoute('sbs_activities.toolbox')->toString();
    foreach ($activities as $activity) {
      $data['activities'][] = [
        'id' => $activity->id(),
        'icon' => $activity->get('field_icon')->getString(),
        'title' => $activity->label(),
        'url' => $activity->toUrl()->toString() . '?destination=' . $toolbox_url,
        'session' => $activity->session_id,
        'prev_lesson_id' => $activity->prev_lesson_id,
        'is_locked' => FALSE,
      ];
    }

    $build['application'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'toolbox-activities',
        'class' => ['activities'],
        'data-application' => Json::encode($data),
      ],
    ];

    $build['#attached'] = [
      'library' => ['sbs_activities/toolbox'],
    ];

    return $build;
  }

}
