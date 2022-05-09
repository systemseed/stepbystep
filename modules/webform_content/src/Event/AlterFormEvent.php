<?php

namespace Drupal\webform_content\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Plugin\WebformHandlerInterface;

/**
 * Event that is fired when the CoursesPage service generates data.
 */
class AlterFormEvent extends Event {

  const EVENT_NAME = 'webform_content_alter_form';

  /**
   * The form representation.
   *
   * @var array
   */
  protected $form;

  /**
   * The form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * The webform submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmission;

  /**
   * The webform handler.
   *
   * @var \Drupal\webform\Plugin\WebformHandlerInterface
   */
  protected $webformHandler;

  /**
   * Constructs the object.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The webform submission.
   * @param \Drupal\webform\Plugin\WebformHandlerInterface $webformHandler
   *   The webform handler.
   */
  public function __construct(array $form, FormStateInterface $formState, WebformSubmissionInterface $webformSubmission, WebformHandlerInterface $webformHandler) {
    $this->form = $form;
    $this->formState = $formState;
    $this->webformSubmission = $webformSubmission;
    $this->webformHandler = $webformHandler;
  }

  /**
   * Get the form.
   */
  public function getForm() {
    return $this->form;
  }

  /**
   * Set the form.
   */
  public function setForm(array $form) {
    $this->form = $form;
  }

  /**
   * Get the webform submission.
   */
  public function getWebformSubmission() {
    return $this->webformSubmission;
  }

  /**
   * Get the webform handler.
   */
  public function getWebformHandler() {
    return $this->webformHandler;
  }

}
