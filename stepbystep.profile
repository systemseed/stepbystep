<?php

/**
 * @file
 * Enables modules and site configuration for a Step By Step installation.
 */

use Drupal\user\Entity\User;

/**
 * Implements hook_install_tasks().
 */
function stepbystep_install_tasks(array &$install_state): array {
  $tasks = [];
  $tasks['stepbystep_content'] = [
    'display_name' => t('Install content'),
  ];
  $tasks['stepbystep_finish_installation'] = [
    'display_name' => t('Finish installation'),
  ];
  return $tasks;
}

/**
 * Finish profile installation process.
 *
 * @param array $install_state
 *   The install state.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function stepbystep_content(array &$install_state): void {
  \Drupal::service('module_installer')->install(['sbs_content']);
}

/**
 * Finish profile installation process.
 *
 * @param array $install_state
 *   The install state.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function stepbystep_finish_installation(array &$install_state): void {
  // Clear all status messages generated by modules installed in previous steps.
  Drupal::service('messenger')->deleteByType('status');
  // Assign user 1 the "developer" role and to the demo storyline.
  $user = User::load(1);
  $user->roles[] = 'developer';
  $storyline = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => 'Women like you']);
  if ($storyline) {
    $user->field_storyline_choice->entity = reset($storyline);
  }
  $user->save();
}

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Allows the profile to alter the site configuration form.
 */
function stepbystep_form_install_configure_form_alter(&$form, $form_state) {
  $form['site_information']['site_name']['#default_value'] = 'Step By Step';

  // Set not-default name for user 1.
  $form['admin_account']['account']['name']['#default_value'] = 'SBS Super Admin';
  $form['admin_account']['account']['name']['#description'] .= ' ' . t('For security reasons avoid generic user names such as "admin".');
}
