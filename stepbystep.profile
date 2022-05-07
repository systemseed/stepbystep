<?php

use Drupal\user\Entity\User;

/**
 * Implements hook_install_tasks().
 */
function stepbystep_install_tasks(array &$install_state): array {
  $tasks = [];
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
function stepbystep_finish_installation(array &$install_state): void {
  // Assign user 1 the "developer" role.
  $user = User::load(1);
  $user->roles[] = 'developer';
  $user->save();
  // TODO: assign to demo user story to avoid empty homepage.
}

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Allows the profile to alter the site configuration form.
 */
function stepbystep_form_install_configure_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  $form['site_information']['site_name']['#default_value'] = 'Step By Step';

  // Set recommended name for user 1.
  $form['admin_account']['account']['name']['#default_value'] = 'SBS Super Admin';
  $form['admin_account']['account']['name']['#description'] .= ' ' . t('For security reasons avoid generic user names such as "admin".');
}
