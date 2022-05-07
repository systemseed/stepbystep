<?php

namespace Drupal\webform_content\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines webform_content_paragraph annotation object.
 *
 * @Annotation
 */
class WebformContentParagraph extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
