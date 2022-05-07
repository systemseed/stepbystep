<?php

/**
 * @file
 * Provide custom fields for the Views module.
 */

/**
 * Implements hook_views_data().
 */
function sbs_ehelpers_views_data() {
  $data['sbs']['table']['group'] = t('SBS');
  $data['sbs']['table']['join'] = [
    // #global is a special flag which allows a table to appear all the time.
    '#global' => [],
  ];

  $data['sbs']['ehelper_requested'] = [
    'title' => t('E-Helper requested'),
    'help' => t('Shows status of e-helper request by the user.'),
    'field' => [
      'id' => 'sbs_ehelper_requested',
      'click sortable' => FALSE,
    ],
  ];

  $data['sbs']['depression_level'] = [
    'title' => t('Depression level'),
    'help' => t('Shows level of depression scored by the user.'),
    'field' => [
      'id' => 'sbs_depression_level',
      'click sortable' => FALSE,
    ],
  ];

  return $data;
}
