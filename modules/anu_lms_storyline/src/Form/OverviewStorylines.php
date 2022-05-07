<?php

namespace Drupal\anu_lms_storyline\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Form\OverviewTerms;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Custom form for listing storylines.
 */
class OverviewStorylines extends OverviewTerms {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $taxonomy_vocabulary = NULL) {
    $form = parent::buildForm($form, $form_state, $taxonomy_vocabulary);
    $form['help']['#access'] = FALSE;
    // Remove non-storyline terms.
    $childrenKeys = Element::children($form['terms']);
    foreach ($childrenKeys as $key) {
      $term = $form['terms'][$key]['#term'];
      if ($term->field_is_storyline->value) {
        // Change 'edit' operation link.
        $form['terms'][$key]['operations']['#links']['edit']['url'] =
          Url::fromRoute('entity.storyline.edit_form', [
            'taxonomy_term' => $term->id(),
          ]);
        continue;
      }

      // Rows don't get completely hidden.
      // See https://www.drupal.org/project/drupal/issues/3123459
      $form['terms'][$key]['#attributes']['style'] = 'display:none';
    }

    return $form;
  }

}
