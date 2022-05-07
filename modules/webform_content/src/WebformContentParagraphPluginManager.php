<?php

namespace Drupal\webform_content;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * WebformContentParagraph plugin manager.
 */
class WebformContentParagraphPluginManager extends DefaultPluginManager {

  /**
   * Constructs WebformContentParagraphPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/WebformContentParagraph',
      $namespaces,
      $module_handler,
      'Drupal\webform_content\WebformContentParagraphInterface',
      'Drupal\webform_content\Annotation\WebformContentParagraph'
    );
    $this->alterInfo('webform_content_paragraph_info');
    $this->setCacheBackend($cache_backend, 'webform_content_paragraph_plugins');
  }

}
