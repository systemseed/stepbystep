<?php

namespace Drupal\sbs_users\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for SBS Users routes.
 */
class HomepageController extends ControllerBase {

  /**
   * The config pages loader.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   */
  protected $configPagesLoader;

  /**
   * The controller constructor.
   *
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $configPagesLoader
   *   The config pages loader.
   */
  public function __construct(ConfigPagesLoaderServiceInterface $configPagesLoader) {
    $this->configPagesLoader = $configPagesLoader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config_pages.loader')
    );
  }

  /**
   * Builds the response.
   */
  public function build($welcomed) {
    $questionnaires = $this->configPagesLoader->getValue('sbs_questionnaires', 'field_questionnaires');
    $user = $this->getUser();
    if (empty($questionnaires) || $user->hasPermission('skip sbs initial steps')) {
      return new RedirectResponse(Url::fromUri('internal:/sessions')->toString(), '302');
    }

    $oneQuestionnaireCompleted = FALSE;
    foreach ($questionnaires as $questionaire) {
      $questionnaireId = $questionaire['target_id'];
      if ($this->isQuestionnaireComplete($questionnaireId, $user)) {
        $oneQuestionnaireCompleted = TRUE;
        continue;
      }
      if (!$welcomed && !$oneQuestionnaireCompleted) {
        return $this->redirect('sbs_users.welcome');
      }
      if (!$welcomed && $oneQuestionnaireCompleted) {
        return $this->redirect('sbs_users.welcome', ['isUserBack' => 'back']);
      }
      return $this->redirect('entity.node.canonical', ['node' => $questionnaireId]);
    }

    // Directs user to select storyline form if no storyline selected.
    if ($user->field_storyline_choice->isEmpty()) {
      return $this->redirect('anu_lms_storyline.view_storylines');
    }
    return new RedirectResponse(Url::fromUri('internal:/sessions')->toString(), '302');
  }

  /**
   * Returns the current user entity.
   */
  protected function getUser() {
    return $this->entityTypeManager()->getStorage('user')->load($this->currentUser()->id());
  }

  /**
   * Check for any completed submissions for the questionnaire.
   */
  protected function isQuestionnaireComplete($questionnaireId, $user) {
    $webformId = $this->entityTypeManager()->getStorage('node')->load($questionnaireId)->webform->target_id;
    $submissions = $this
      ->entityTypeManager()
      ->getStorage('webform_submission')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', $user->id())
      ->condition('completed', 0, '>')
      ->condition('webform_id', $webformId)
      ->execute();

    return !empty($submissions);
  }

}
