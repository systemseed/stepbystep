<?php

namespace Drupal\sbs_ehelpers\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an E-helper request block.
 *
 * @Block(
 *   id = "sbs_ehelpers_request",
 *   admin_label = @Translation("E-helper request"),
 *   category = @Translation("SBS")
 * )
 */
class RequestBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config_pages.loader service.
   *
   * @var \Drupal\example\ExampleInterface
   */
  protected $configPagesLoader;

  /**
   * Constructs a new RequestBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $config_pages_loader
   *   The config_pages.loader service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigPagesLoaderServiceInterface $config_pages_loader) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configPagesLoader = $config_pages_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config_pages.loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $configPage = $this->configPagesLoader->load('request_ehelper');
    $title = $this->configPagesLoader->getValue($configPage, 'field_ehelper_popup_title', 0);
    $text = $this->configPagesLoader->getFieldView($configPage, 'field_ehelper_popup_text');
    $accept = $this->configPagesLoader->getValue($configPage, 'field_ehelper_popup_accept_text', 0);
    $cancel = $this->configPagesLoader->getValue($configPage, 'field_ehelper_popup_cancel_text', 0);
    $questionnaire = $this->configPagesLoader->getValue($configPage, 'field_ehelper_questionnaire', 0);

    $cacheability = new CacheableMetadata();
    $cacheability->addCacheableDependency($configPage);

    $build['content'] = [
      '#theme' => 'sbs_ehelpers_request',
      '#title' => $title['value'] ?? $this->t('Would you like to request an E-helper now?'),
      '#text' => $text,
      '#accept' => $accept['value'] ?? $this->t('Yes, request E-helper'),
      '#cancel' => $cancel['value'] ?? $this->t('No thanks'),
      '#questionnaire_url' => isset($questionnaire['target_id']) ? Url::fromRoute('entity.node.canonical', ['node' => $questionnaire['target_id']])->toString() : '#',
    ];
    $build['#attached'] = [
      'library' => 'sbs_ehelpers/request-popup',
      'drupalSettings' => [
        'sbsEhelpers' => [
          'cancelPath' => Url::fromRoute('sbs_ehelpers.dismiss_ehelper_popup')->toString(),
          'displayPopupPath' => Url::fromRoute('sbs_ehelpers.display_popup')->toString(),
        ],
      ],
    ];

    $cacheability->applyTo($build);
    return $build;
  }

}
