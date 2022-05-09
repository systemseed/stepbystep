<?php

namespace Drupal\sbs_ehelpers\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a SBS E-helpers form.
 */
class AssignEhelper extends FormBase implements ConfirmFormInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * AssignEhelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sbs_ehelpers_assign_ehelper';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL) {
    // To retrieve later in the submit.
    $form_state->set('userToAssign', $user);

    $userStorage = $this->entityTypeManager->getStorage('user');

    $resultEhelpers = $userStorage->getQuery()
      ->condition('roles', 'e_helper', 'CONTAINS')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->execute();

    // Plain array with E-helper ids.
    $assignedEhelpers = array_map(function ($item) {
      return $item['target_id'];
    }, $user->get('field_assigned_ehelpers')->getValue());

    $form['subtitle'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('for the participant: <strong>@name</strong>', ['@name' => $user->label()]),
    ];

    $header = [
      'ehelper' => $this->t('E-helper'),
      'operation' => '',
    ];
    if (empty($resultEhelpers)) {
      $form['note'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('There is no e-helpers available'),
      ];
      return $form;
    }

    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
    ];
    foreach ($resultEhelpers as $ehelperId) {
      $ehelper = $userStorage->load($ehelperId);

      $form['table'][] = [
        'ehelper' => ['#markup' => $ehelper->label()],
        'operation' => [
          '#title' => $ehelper->label(),
          '#title_display' => 'invisible',
          '#type' => 'radio',
          '#name' => 'ehelper',
          '#return_value' => $ehelperId,
          '#default_value' => in_array($ehelperId, $assignedEhelpers, TRUE) ? $ehelperId : FALSE,
        ],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->getCancelText(),
      '#ajax' => [
        'callback' => [$this, 'cancelModalFormAjax'],
        'event' => 'click',
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->getConfirmText(),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => [$this, 'submitModalFormAjax'],
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('view.unassigned_users.page_1');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this
      ->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this
      ->t('Assign');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName() {
    return 'confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();

    if (empty($input['ehelper'])) {
      return;
    }
    $form_state->setValue('ehelper', intval($input['ehelper']));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitModalFormAjax(array &$form, FormStateInterface $form_state) {
    $user = $form_state->get('userToAssign');
    $ehelperId = $form_state->getValue('ehelper');
    if (!$ehelperId) {
      return $this->cancelModalFormAjax($form, $form_state);
    }
    $user->field_assigned_ehelpers->target_id = $ehelperId;
    $user->field_user_state->value = 'active';
    $user->save();

    $response = new AjaxResponse();

    $ehelper = $this->entityTypeManager->getStorage('user')->load($ehelperId);
    $selector = '.ehelper-selection';

    $ehelper->label();

    // Build the link for removing.
    $removeUrl = Url::fromRoute('sbs_ehelpers.remove_ehelper', [
      'user' => $user->id(),
      'ehelper' => $ehelperId,
    ]);
    $removeLink = Link::fromTextAndUrl($this->t('Remove'), $removeUrl);
    $removeLinkBuild = $removeLink->toRenderable();
    $removeLinkBuild['#attributes'] = [
      'class' => ['use-ajax', 'ehelper-assign'],
    ];

    // Build the E-helper name linked to the assign popup.
    $assignUrl = Url::fromRoute('sbs_ehelpers.assign_ehelper', [
      'user' => $user->id(),
    ]);
    $assignLink = Link::fromTextAndUrl($this->t('Edit'), $assignUrl);
    $assignLinkBuild = $assignLink->toRenderable();
    $assignLinkBuild['#attributes'] = [
      'class' => ['use-ajax', 'ehelper-assign'],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => json_encode(['width' => 900]),
    ];

    $paragraph = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => [
        'class' => ['ehelper-selection', 'ehelper-remove-' . $ehelperId],
      ],
      'name' => ['#markup' => $ehelper->label()],
      'actions' => [
        '#type' => 'container',
        'assign' => $assignLinkBuild,
        'remove' => $removeLinkBuild,
      ],
    ];

    $response->addCommand(new ReplaceCommand($selector, $paragraph));
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  /**
   * AJAX callback handler that closes the modal.
   */
  public function cancelModalFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

}
