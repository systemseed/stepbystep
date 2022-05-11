<?php

namespace Drupal\sbs_users\EventSubscriber;

use Drupal\anu_lms\CourseProgress;
use Drupal\anu_lms\Event\LessonCompletedEvent;
use Drupal\anu_lms\Lesson;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sbs_users\Profile;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to IncidentEvents::NEW_REPORT events and react to new reports.
 *
 * In this example we subscribe to all IncidentEvents::NEW_REPORT events and
 * point to two different methods to execute when the event is triggered. In
 * each method we have some custom logic that determines if we want to react to
 * the event by examining the event object, and the displaying a message to the
 * user indicating whether or not that method reacted to the event.
 *
 * By convention, classes subscribing to an event live in the
 * Drupal/{module_name}/EventSubscriber namespace.
 *
 * @ingroup events_example
 */
class LessonCompletedSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * The course page service.
   *
   * @var \Drupal\anu_lms\CourseProgress
   */
  protected $courseProgress;

  /**
   * The Lesson service.
   *
   * @var \Drupal\anu_lms\Lesson
   */
  protected $lesson;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Storyline handler.
   *
   * @var \Drupal\sbs_users\Profile
   */
  protected Profile $profile;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\anu_lms\Lesson $lesson
   *   The lesson service.
   * @param \Drupal\anu_lms\CourseProgress $courseProgress
   *   The course progress.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\sbs_users\Profile $profile
   *   The profile.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(Lesson $lesson, CourseProgress $courseProgress, EntityTypeManagerInterface $entityTypeManager, Profile $profile, LoggerInterface $logger) {
    $this->lesson = $lesson;
    $this->courseProgress = $courseProgress;
    $this->entityTypeManager = $entityTypeManager;
    $this->profile = $profile;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['anu_lms.lesson_completed'][] = ['onLessonCompleted'];

    return $events;
  }

  /**
   * Set user to completed status if all lessons are completed.
   */
  public function onLessonCompleted(LessonCompletedEvent $event) {
    $account = $event->getAccount();
    /** @var \Drupal\user\Entity\User $user */
    $user = $this->entityTypeManager->getStorage('user')->load($account->id());
    if ($user->get('field_user_state')->value == 'completed') {
      return;
    }
    $profile_progress = $this->profile->getProgress($user);

    if ($profile_progress->getUntranslatedString() === 'All sessions completed') {
      $user->set('field_user_state', 'completed');
      $user->save();
      $this->logger->info('The user has moved to completed state as they completed all sessions from the storyline.');
    }
  }

}
