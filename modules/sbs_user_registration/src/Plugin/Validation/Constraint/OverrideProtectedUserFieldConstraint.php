<?php

namespace Drupal\sbs_user_registration\Plugin\Validation\Constraint;

use Drupal\user\Plugin\Validation\Constraint\ProtectedUserFieldConstraint;

/**
 * Checks if the plain text password is provided for editing a protected field.
 *
 * @Constraint(
 *   id = "ProtectedUserField",
 *   label = @Translation("Password required for protected field change", context = "Validation")
 * )
 */
class OverrideProtectedUserFieldConstraint extends ProtectedUserFieldConstraint {

}
