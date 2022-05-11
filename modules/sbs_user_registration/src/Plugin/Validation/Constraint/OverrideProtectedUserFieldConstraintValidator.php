<?php

namespace Drupal\sbs_user_registration\Plugin\Validation\Constraint;

use Drupal\user\Plugin\Validation\Constraint\ProtectedUserFieldConstraintValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Validates the ProtectedUserFieldConstraint constraint.
 */
class OverrideProtectedUserFieldConstraintValidator extends ProtectedUserFieldConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!isset($items)) {
      return;
    }

    /** @var \Drupal\user\UserInterface $account */
    $account = $items->getEntity();
    if (!isset($account) || !empty($account->_skipProtectedUserFieldConstraint)) {
      // Looks like we are validating a field not being part of a user, or the
      // constraint should be skipped, so do nothing.
      return;
    }

    // Only validate for existing entities and if this is the current user.
    if (!$account->isNew() && $account->id() == $this->currentUser->id()) {
      // Special case for the email, it can be empty.
      /** @var \Drupal\Core\Field\FieldItemListInterface $items */
      $field = $items->getFieldDefinition();
      if ($field->getName() == 'mail') {
        return;
      }
      parent::validate($items, $constraint);
    }
  }

}
