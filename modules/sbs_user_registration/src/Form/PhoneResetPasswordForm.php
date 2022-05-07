<?php

namespace Drupal\sbs_user_registration\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\otp_login\Otp;

/**
 * Custom form for password reset for phone registered users.
 */
class PhoneResetPasswordForm extends FormBase {

  /**
   * The user.
   *
   * @var \Drupal\user\UserInterface
   */
  private $user;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The otp service.
   *
   * @var \Drupal\otp_login\Otp
   */
  protected Otp $otp;

  /**
   * Drupal settings service.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected Settings $settings;

  /**
   * Constructs a FormBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   * @param \Drupal\otp_login\Otp $otp
   *   The otp service.
   * @param \Drupal\Core\Site\Settings $settings
   *   Drupal settings service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, Otp $otp, Settings $settings) {
    $this->otp = $otp;
    $this->entityTypeManager = $entityTypeManager;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('otp_login.OTP'),
      $container->get('settings'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sbs_user_registration_phone_reset_password';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $user = $this->findUser($form_state->getValue('name'));
    if (empty($user)) {
      $form_state->setError($form, $this->t('User not registered'));
    }
    else {
      $this->user = $user;
    }
  }

  /**
   * Finds a user based on phone number.
   *
   * @param string $name
   *   A phone number.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   A user or void.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function findUser(string $name) {
    if (empty($name)) {
      return FALSE;
    }
    $accounts = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $name]);
    $account = reset($accounts);
    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = [
      '#type' => 'tel',
      '#title' => 'Phone Number',
      '#required' => TRUE,
      '#maxlength' => 15,
      '#description' => $this->t('We will send an SMS to verify your account'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset password'),
      '#attributes' => [
        'class' => ['mdc-button--raised'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->otp->generateOtp($this->user->getAccountName());
    $this->messenger()->addMessage($this->t('A verification code has been sent to the phone provided.'));

    $route_parameters = [
      'user' => $this->user->id(),
      'hash' => hash_hmac('sha256', $this->user->id(), $this->settings->get('hash_salt')),
    ];
    $options = ['query' => ['reset_password' => 1]];
    $form_state->setRedirect('sbs_user_registration.mobile_registration_verify', $route_parameters, $options);
  }

}
