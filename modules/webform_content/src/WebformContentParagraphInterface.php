<?php

namespace Drupal\webform_content;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for webform_content_paragraph plugins.
 */
interface WebformContentParagraphInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

  /**
   * Returns a valid element key.
   *
   * @return string
   *   The key for the form element.
   */
  public function getElementKey(EntityInterface $entity);

}
