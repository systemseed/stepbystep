<?php

namespace Drupal\sbs_user_registration\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\otp_login\Otp;

/**
 * Custom form for phone verification during registration.
 */
class PhoneRegistrationVerifyForm extends ContentEntityForm {

  /**
   * The otp service.
   *
   * @var \Drupal\otp_login\Otp
   */
  protected $otp;

  /**
   * Whether this page is part of reset password flow.
   *
   * @var bool
   */
  protected bool $resetPassword;

  /**
   * User data handler.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected UserDataInterface $userData;

  /**
   * Service providing drupal settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected Settings $settings;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\otp_login\Otp $otp
   *   The time service.
   * @param \Drupal\user\UserDataInterface $user_data
   *   User data service.
   * @param \Drupal\Core\Site\Settings $settings
   *   Drupal settings service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, Otp $otp, UserDataInterface $user_data, Settings $settings) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->otp = $otp;
    $this->userData = $user_data;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('otp_login.OTP'),
      $container->get('user.data'),
      $container->get('settings'),
    );
  }

  /**
   * Returns title of form based on request.
   */
  public function getTitle(): string {
    $reset_password = $this->getRequest()->query->get('reset_password');
    return empty($reset_password) ? 'Sign up' : 'Reset password';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['verification'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Verification code'),
    ];

    // Add link that actually submits the form with a different action.
    $form['resend'] = [
      '#type' => 'submit',
      '#value' => $this->t('Resend code'),
      '#submit' => ['::resendCode'],
      '#limit_validation_errors' => [],
      '#attributes' => [
        'class' => ['sbs-button-link'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $verification_code = $form_state->getValue('verification');
    if (empty($verification_code)) {
      $form_state->setError($form, $this->t('Verification code can not be empty.'));
    }
    elseif ($this->otp->validateOtp($verification_code, $this->entity->name->value)) {
      $form_state->setError($form, $this->t('Invalid verification code. Request a new one and try again.'));
    }
  }

  /**
   * Access check for the page with verification code form.
   *
   * @param string $user
   *   User ID being verified.
   * @param string $hash
   *   Securely generated code.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   Access result for the page.
   */
  public function access(string $user, string $hash) {
    $expected_hash = hash_hmac('sha256', $user, $this->settings->get('hash_salt'));
    if (hash_equals($expected_hash, $hash)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $submit_label = $this->getPasswordResetParameter() ? $this->t('Reset Password') : $this->t('Create account');

    $element = parent::actions($form, $form_state);
    $element['submit']['#value'] = $submit_label;
    $element['submit']['#states'] = [
      'disabled' => [
        ':input[name="verification"]' => ['empty' => TRUE],
      ],
    ];

    return $element;
  }

  /**
   * Submit callback for code resend button.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Submitted form state.
   */
  public function resendCode(array &$form, FormStateInterface $form_state) {
    // Get timestamp of the last user request to receive the code.
    $data = $this->userData->get('otp_login', $this->getEntity()->id(), 'otp_user_data');
    $last_otp_timestamp = 0;
    if (!empty($data['otps'])) {
      $last_otp_request = array_pop($data['otps']);
      if (!empty($last_otp_request['otp_time'])) {
        $last_otp_timestamp = $last_otp_request['otp_time'];
      }
    }

    // Get current timestamp.
    $current_timestamp = $this->time->getRequestTime();

    // We wait for at least 60 seconds between attempts to re-send the code.
    if ($current_timestamp - $last_otp_timestamp < 60) {
      $remaining_to_wait = 60 - $current_timestamp + $last_otp_timestamp;
      $this->messenger()
        ->addWarning($this->t('Please, wait for @remaining more seconds before you can make another attempt to resend code.', [
          '@remaining' => $remaining_to_wait,
        ]));
    }
    // All checks passed - we can resend the code.
    else {
      $this->otp->generateOtp($this->entity->name->value);
      $this->messenger()
        ->addMessage($this->t("We've sent another verification code. It should arrive soon."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->getPasswordResetParameter()) {
      $url = user_pass_reset_url($this->getUser());
      $form_state->setRedirectUrl(Url::fromUri($url));
    }
    else {
      $form_state->setRedirect('<front>');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->status = 1;
    parent::save($form, $form_state);
    if (!$this->getPasswordResetParameter()) {
      $this->otp->userOtpLogin($form_state->getValue('verification'), $this->entity->name->value);
    }
  }

  /**
   * Returns the user.
   *
   * @return \Drupal\Core\Entity\EntityInterface|UserInterface|false
   *   The user.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getUser() {
    $account = $this->entityTypeManager->getStorage('user')->load($this->entity->id());
    return $account;
  }

  /**
   * Returns whether password reset flow is active.
   */
  private function getPasswordResetParameter() {
    if (empty($this->resetPassword)) {
      $this->resetPassword = !empty($this->getRequest()->query->get('reset_password'));
    }
    return $this->resetPassword;
  }

}
