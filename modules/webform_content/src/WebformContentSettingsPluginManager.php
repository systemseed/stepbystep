<?php

namespace Drupal\webform_content;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * WebformContentSettings plugin manager.
 */
class WebformContentSettingsPluginManager extends DefaultPluginManager {

  /**
   * Constructs WebformContentSettingsPluginManager object.
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
      'Plugin/WebformContentSettings',
      $namespaces,
      $module_handler,
      'Drupal\webform_content\WebformContentSettingsInterface',
      'Drupal\webform_content\Annotation\WebformContentSettings'
    );
    $this->alterInfo('webform_content_settings_info');
    $this->setCacheBackend($cache_backend, 'webform_content_settings_plugins');
  }

}
