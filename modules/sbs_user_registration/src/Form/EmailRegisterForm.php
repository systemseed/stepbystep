<?php

namespace Drupal\sbs_user_registration\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RegisterForm;

/**
 * Custom form for email registration.
 */
class EmailRegisterForm extends RegisterForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['account']['name']['#access'] = FALSE;
    $form['account']['name']['#required'] = FALSE;

    // If current user is not admin then we change the default description
    // to be more personalized.
    if (!$this->entity->access('create')) {
      $form['account']['mail']['#description'] = $this->t('We will send you an email to verify your account');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    // If current user is not admin then we change the default button label.
    if (!$this->entity->access('create')) {
      $element['submit']['#value'] = $this->t('Verify');
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('name', $form_state->getValue('mail'));
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // If current user is admin then we don't change the registration behavior,
    // (when admin creates a user from the backend). Otherwise, we set up
    // another workflow in case of self-registration.
    if ($this->entity->access('create')) {
      parent::save($form, $form_state);
    }
    else {
      $account = $this->entity;
      $account->status = 0;
      $account->save();
      $op = 'register_no_approval_required';
      if (_user_mail_notify($op, $account)) {
        $this->messenger()
          ->addStatus($this->t('A welcome message with further instructions has been sent to your email address.'));
      }
      $form_state->setRedirect('<front>');
    }
  }

}
