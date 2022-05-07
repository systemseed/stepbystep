<?php

namespace Drupal\sbs_ehelpers\Plugin\views\field;

use Drupal\Core\Session\AccountInterface;
use Drupal\sbs_ehelpers\SbsEhelpersQuestionnaire;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to display E-helper requested status.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("sbs_ehelper_requested")
 */
class EhelperRequested extends FieldPluginBase {

  /**
   * E-helper questionnaire instance.
   *
   * @var \Drupal\sbs_ehelpers\SbsEhelpersQuestionnaire
   */
  protected SbsEhelpersQuestionnaire $ehelperQuestionnaire;

  /**
   * Constructs a PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\sbs_ehelpers\SbsEhelpersQuestionnaire $ehelperQuestionnaire
   *   E-helper questionnaire instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SbsEhelpersQuestionnaire $ehelperQuestionnaire) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ehelperQuestionnaire = $ehelperQuestionnaire;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sbs_ehelpers.questionnaire')
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

    return $this->ehelperQuestionnaire->hasUserQuestionnairesSubmitted($values->_entity)
      ? $this->t('Yes')
      : $this->t('No');
  }

}
