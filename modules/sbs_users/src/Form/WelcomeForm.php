<?php

namespace Drupal\sbs_users\Form;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Welcome the user.
 */
class WelcomeForm extends FormBase {

  /**
   * The config pages loader.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   */
  protected $configPagesLoader;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sbs_users_welcome_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static();
    $form->setConfigLoader($container->get('config_pages.loader'));
    return $form;
  }

  /**
   * Inject the config pages loader.
   *
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $configPagesLoader
   *   The config pages loader.
   */
  public function setConfigLoader(ConfigPagesLoaderServiceInterface $configPagesLoader) {
    $this->configPagesLoader = $configPagesLoader;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $isUserBack = FALSE) {
    if ($isUserBack) {
      $header = $this->configPagesLoader->getValue('sbs_questionnaires_welcome_back', 'field_welcome_back_header');
      $text = $this->configPagesLoader->getValue('sbs_questionnaires_welcome_back', 'field_welcome_back_text');
      $button = $this->configPagesLoader->getValue('sbs_questionnaires_welcome_back', 'field_welcome_back_button');
    }
    else {
      $header = $this->configPagesLoader->getValue('sbs_questionnaires_first_welcome', 'field_welcome_header');
      $text = $this->configPagesLoader->getValue('sbs_questionnaires_first_welcome', 'field_welcome_text');
      $button = $this->configPagesLoader->getValue('sbs_questionnaires_first_welcome', 'field_welcome_button');
    }

    if (!empty($header)) {
      $header = reset($header);
      $form['header'] = [
        '#type' => 'html_tag',
        '#tag' => 'h1',
        '#value' => $header['value'],
      ];
    }

    if (!empty($text)) {
      $text = reset($text);
      $form['text'] = [
        '#type' => 'processed_text',
        '#text' => $text['value'],
        '#format' => $text['format'],
      ];
    }
    if (!empty($button)) {
      $button = reset($button);
      $button = $button['value'];
    }
    else {
      $button = $this->t('Submit');
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $button,
      '#attributes' => ['class' => ['mdc-button--raised ']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('sbs_users.homepage', ['welcomed' => 'continue']);

  }

}
