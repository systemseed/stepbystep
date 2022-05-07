<?php

namespace Drupal\sbs_user_registration\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Form\UserLoginForm;

/**
 * Custom login form for phone login.
 */
class PhoneLoginForm extends UserLoginForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sbs_user_registration_phone_login';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['name'] = [
      '#type' => 'tel',
      '#title' => 'Phone Number',
      '#required' => TRUE,
      '#maxlength' => 12,
      '#description' => NULL,
    ];

    $form['pass']['#description'] = NULL;

    $reset_password_url = Url::fromRoute('sbs_user_registration.password_reset_mobile');
    $form['password_reset_link'] = [
      '#type' => 'link',
      '#url' => $reset_password_url,
      '#title' => $this->t('Reset Password'),
      '#attributes' => [
        'id' => ['user-reset-password-link'],
        'class' => ['sbs-text-link'],
      ],
    ];

    $form['bottom'] = [
      '#type' => 'fieldset',
      '#weight' => 1000,
    ];

    $register_prompt_string = $this->t("Don't have an account yet?");
    $form['bottom']['register_prompt'] = [
      '#markup' => "<p id='register-prompt'>$register_prompt_string</p>",
      '#weight' => 11,
    ];

    $phone_registration_url = Url::fromRoute('sbs_static_pages.registration_consent_page');
    $form['bottom']['phone_register_link'] = [
      '#type' => 'link',
      '#url' => $phone_registration_url,
      '#title' => $this->t('Sign Up'),
      '#attributes' => [
        'id' => ['user-phone-login-link'],
        'class' => ['sbs-text-link'],
      ],
      '#weight' => 12,
    ];

    return $form;
  }

}
