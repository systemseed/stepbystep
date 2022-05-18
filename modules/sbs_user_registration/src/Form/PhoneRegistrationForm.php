<?php

namespace Drupal\sbs_user_registration\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\otp_login\Otp;
use Drupal\user\Entity\User;
use Drupal\user\RegisterForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom registration form for phone registration.
 */
class PhoneRegistrationForm extends RegisterForm {

  /**
   * The otp service.
   *
   * @var \Drupal\otp_login\Otp
   */
  protected $otp;

  /**
   * Settings service.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected Settings $settings;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, ModuleHandlerInterface $moduleHandler, Otp $otp, Settings $settings) {
    $this->setEntity(User::create());
    $this->setModuleHandler($moduleHandler);
    $this->otp = $otp;
    $this->settings = $settings;

    parent::__construct($entity_repository, $language_manager, $entity_type_bundle_info, $time);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('module_handler'),
      $container->get('otp_login.OTP'),
      $container->get('settings'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sbs_user_registration_phone_registration';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['field_user_state']['#access'] = FALSE;
    $form['account']['mail']['#access'] = FALSE;
    $form['account']['mail']['#required'] = FALSE;

    $form['account']['name'] = [
      '#type' => 'tel',
      '#title' => 'Phone Number',
      '#required' => TRUE,
      '#maxlength' => 15,
      '#description' => $this->t('We will send an SMS to verify your account'),
    ];

    $form['account']['pass']['#description'] = NULL;

    $form['#after_build'][] = 'sbs_user_registration_after_build';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('mail', 'nobody@example.com');
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $element['submit']['#value'] = $this->t('Verify');
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('mail', '');
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->blockUser();

    $this->otp->generateOtp($this->entity->name->value);
    $this->messenger()->addMessage($this->t('A verification code has been sent to the phone provided.'));

    $form_state->setRedirect('sbs_user_registration.mobile_registration_verify', [
      'user' => $this->entity->id(),
      'hash' => hash_hmac('sha256', $this->entity->id(), $this->settings->get('hash_salt')),
    ]);
  }

  /**
   * Blocks the current user during verification.
   */
  private function blockUser() {
    $this->entity->status = 0;
    $this->entity->save();
  }

}
