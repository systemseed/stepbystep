<?php

namespace Drupal\sbs_user_notes\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides note add form + list of participant notes.
 */
class NotesForm extends FormBase {

  /**
   * Entity type manager instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Date formatter instance.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * Constructs the form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user account.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   Date formatter instance.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, DateFormatterInterface $date_formatter) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('date.formatter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sbs_user_notes_note_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $participant = NULL) {
    // If there's no participant argument passed then we can't proceed,
    // because notes get attached and displayed against a certain participant.
    if (empty($participant)) {
      return [];
    }

    // Add form ID for ajax replacements.
    $form['#id'] = 'sbs-notes-form';

    // Pass through participant user object as a value without displaying it
    // in the form.
    $form['participant'] = [
      '#type' => 'value',
      '#value' => $participant,
    ];

    if ($this->currentUser->hasPermission('create note entities')) {
      $form['note_add'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['note-add'],
        ],
      ];

      $form['note_add']['note'] = [
        '#type' => 'textarea',
        '#placeholder' => $this->t('Write a note here...'),
        // We don't put #required here, because it's better to check for the
        // emptiness of this field in the validation method & show user-friendly
        // error message instead of the Drupal default one.
      ];

      $form['note_add']['actions'] = ['#type' => 'actions'];
      $form['note_add']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add note'),
        '#button_type' => 'primary',
        '#ajax' => [
          'callback' => '::ajaxCallback',
          'wrapper' => 'sbs-notes-form',
        ],
      ];
    }

    $form['notes'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['notes-list'],
      ],
    ];

    // Load all notes left for the participant ordered by creation date.
    $note_ids = $this->entityTypeManager->getStorage('note')
      ->getQuery()
      ->condition('type', 'note')
      ->condition('field_participant', $participant->id())
      ->sort('created', 'DESC')
      ->execute();

    if (!empty($note_ids) && is_array($note_ids)) {
      /** @var \Drupal\eck\EckEntityInterface[] $notes */
      $notes = $this->entityTypeManager->getStorage('note')
        ->loadMultiple($note_ids);
      foreach ($notes as $note) {
        $created_timestamp = $note->get('created')->getString();
        $author = $note->getOwner();

        // Make sure the current user can view the note.
        $can_view_any_note = $this->currentUser->hasPermission('view any note entities');
        $can_view_own_note = $this->currentUser->hasPermission('view own note entities');
        $is_own_note = $author->id() === $this->currentUser->id();
        if ($can_view_any_note || ($is_own_note && $can_view_own_note)) {
          $form['notes'][] = [
            '#theme' => 'sbs_note',
            // Cover the case when an author of the note has been deleted.
            '#author' => !empty($author) ? $author->getDisplayName() : $this->t('Unknown user'),
            // Format the note creation date accordingly.
            '#created' => $this->dateFormatter->format($created_timestamp, 'long'),
            // Get note with retaining the original text formatting.
            '#note' => $note->get('field_note')->getString(),
          ];
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $note = trim($form_state->getValue('note'));
    if (empty($note)) {
      $form_state->setErrorByName('note', $this->t("The note can't be empty."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $note = $this->entityTypeManager->getStorage('note')->create([
      'type' => 'note',
      'uid' => $this->currentUser->id(),
      'field_participant' => $form_state->getValue('participant'),
      'field_note' => $form_state->getValue('note'),
    ]);
    $note->save();

    // Rebuild is important for ajax callbacks - it lets do full rebuild
    // of the form before it's re-rendered by Drupal.
    $form_state->setRebuild(TRUE);
  }

  /**
   * Ajax callback for "Add note" button.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Modified form array after ajax.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state): array {
    // After a successful form submission we need to make sure that the
    // entered note does not preserve in the form anymore, otherwise it
    // would look weird.
    if (!$form_state->getErrors()) {
      $form['note_add']['note']['#value'] = '';
    }

    // Just return the form, telling Drupal to re-render the entire form.
    return $form;
  }

}
