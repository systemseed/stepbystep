<?php

namespace Drupal\sbs_activities;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies ANU LMS normalizer.
 */
class SbsActivitiesServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('anu_lms.normalizer.node')) {
      $definition = $container->getDefinition('anu_lms.normalizer.node');
      $definition->setClass('Drupal\sbs_activities\Normalizer\NodeNormalizer');
    }
  }

}
