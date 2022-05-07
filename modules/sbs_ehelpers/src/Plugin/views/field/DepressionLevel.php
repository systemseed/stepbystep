<?php

namespace Drupal\sbs_ehelpers\Plugin\views\field;

use Drupal\Core\Session\AccountInterface;
use Drupal\sbs_questionnaire\QuestionnaireService;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\webform_content_score\Score;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to display E-helper requested status.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("sbs_depression_level")
 */
class DepressionLevel extends FieldPluginBase {

  /**
   * Questionnaire service instance.
   *
   * @var \Drupal\sbs_questionnaire\QuestionnaireService
   */
  protected QuestionnaireService $questionnaire;

  /**
   * Webform score service instance.
   *
   * @var \Drupal\webform_content_score\Score
   */
  protected Score $score;

  /**
   * Constructs a PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\sbs_questionnaire\QuestionnaireService $questionnaire_service
   *   Questionnaire service instance.
   * @param \Drupal\webform_content_score\Score $score
   *   Webform score service instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QuestionnaireService $questionnaire_service, Score $score) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->questionnaire = $questionnaire_service;
    $this->score = $score;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sbs_questionnaire.service'),
      $container->get('webform_content_score.score')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (!($values->_entity instanceof AccountInterface)) {
      return '';
    }

    $scoredQuestionnaire = $this->questionnaire->getScoredQuestionnaire();
    if (empty($scoredQuestionnaire)) {
      return $this->t('Not applicable');
    }

    $score = $this->score->getScore($scoredQuestionnaire, $values->_entity);
    if (empty($score)) {
      return $this->t('Not answered yet');
    }

    // Fallback to pure score, not sure if it makes any sense though, but
    // couldn't figure out a better fallback.
    return $this->score->getScoreLevelTitle($scoredQuestionnaire, $score) ?: $score;
  }

}
