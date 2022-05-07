<?php

namespace Drupal\webform_content\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_content\Event\OverrideSettingsEvent;
use Drupal\webform_content\Event\AlterFormEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform submission test handler.
 *
 * @WebformHandler(
 *   id = "webform_content",
 *   label = @Translation("Questionnaire handler"),
 *   category = @Translation("Questionnaire"),
 *   description = @Translation("Triggers events for questionnaires"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class WebformContent extends WebformHandlerBase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   *
   * IMPORTANT:
   * Webform handlers are initialized and serialized when they are attached to a
   * webform. Make sure not include any services as a dependency injection
   * that directly connect to the database. This will prevent
   * "LogicException: The database connection is not serializable." exceptions
   * from being thrown when a form is serialized via an Ajax callback and/or
   * form build.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->eventDispatcher = $container->get('event_dispatcher');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'webform_content_node' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['webform_content_node'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Matching node for this webform'),
      '#default_value' => $this->configuration['webform_content_node'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['webform_content_node'] = $form_state->getValue('webform_content_node');
  }

  /**
   * {@inheritdoc}
   */
  public function alterElements(array &$elements, WebformInterface $webform) {
  }

  /**
   * {@inheritdoc}
   */
  public function alterElement(array &$element, FormStateInterface $form_state, array $context) {
  }

  /**
   * {@inheritdoc}
   */
  public function overrideSettings(array &$settings, WebformSubmissionInterface $webform_submission) {
    $event = new OverrideSettingsEvent($settings, $webform_submission, $this);
    $this->eventDispatcher->dispatch(OverrideSettingsEvent::EVENT_NAME, $event);
    $settings = $event->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $event = new AlterFormEvent($form, $form_state, $webform_submission, $this);
    $this->eventDispatcher->dispatch(AlterFormEvent::EVENT_NAME, $event);
    $form = $event->getForm();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
  }

  /**
   * {@inheritdoc}
   */
  public function preCreate(array &$values) {
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(WebformSubmissionInterface $webform_submission) {
  }

  /**
   * {@inheritdoc}
   */
  public function postLoad(WebformSubmissionInterface $webform_submission) {
  }

  /**
   * {@inheritdoc}
   */
  public function preDelete(WebformSubmissionInterface $webform_submission) {
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
  }

  /**
   * {@inheritdoc}
   */
  public function access(WebformSubmissionInterface $webform_submission, $operation, AccountInterface $account = NULL) {
    $access_result = parent::access($webform_submission, $operation, $account);
    return $access_result->setCacheMaxAge(0);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessConfirmation(array &$variables) {
  }

  /**
   * {@inheritdoc}
   */
  public function createHandler() {
  }

  /**
   * {@inheritdoc}
   */
  public function updateHandler() {
  }

  /**
   * {@inheritdoc}
   */
  public function deleteHandler() {
  }

  /**
   * {@inheritdoc}
   */
  public function accessElement(array &$element, $operation, AccountInterface $account = NULL) {
    $access_result = parent::accessElement($element, $operation, $account);
    return $access_result->setCacheMaxAge(0);
  }

  /**
   * {@inheritdoc}
   */
  public function createElement($key, array $element) {
  }

  /**
   * {@inheritdoc}
   */
  public function updateElement($key, array $element, array $original_element) {
  }

  /**
   * {@inheritdoc}
   */
  public function deleteElement($key, array $element) {
  }

}
