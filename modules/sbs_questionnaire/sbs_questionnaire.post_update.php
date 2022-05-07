<?php

/**
 * @file
 * Post-update hooks for SBS questionnaire module.
 */

use Drupal\config_pages\Entity\ConfigPages;

/**
 * Create default config pages.
 */
function sbs_questionnaire_post_update_default_config_page() {
  do {
    $current = ConfigPages::load('sbs_questionnaires');
    if ($current) {
      $current->delete();
    }
  } while ($current);

  ConfigPages::create([
    'type' => 'sbs_questionnaires',
    'context' => serialize([['language' => 'en']]),
  ])->save();

  ConfigPages::create([
    'type' => 'sbs_questionnaires_first_welcome',
    'context' => serialize([['language' => 'en']]),
    'field_welcome_button' => 'Start 2-minute questionnaire',
    'field_welcome_header' => 'Thanks for joining, it\'s great to have you here.',
    'field_welcome_text' => [
      'value' => '<p>We have  prepared some questions to better understand how Step-by-Step can help you.</p><p>Duration: 2 minutes </p>',
      'format' => 'minimal_html',
    ],
  ])->save();

  ConfigPages::create([
    'type' => 'sbs_questionnaires_welcome_back',
    'context' => serialize([['language' => 'en']]),
    'field_welcome_back_button' => 'Continue 2-minute questionnaire',
    'field_welcome_back_header' => 'Welcome back to Step-by-Step',
    'field_welcome_back_text' => [
      'value' => '<p>We have  prepared some questions to better understand how Step-by-Step can help you.</p><p>Duration: 2 minutes </p>',
      'format' => 'minimal_html',
    ],
  ])->save();
}
