<?php

namespace Drupal\localgov_workflows_notifications\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an implementation for menu link plugins.
 */
class LocalgovWorkflowsNotificationsContentByOwner extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected string $basePluginId;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];

    // Only create the menu link if the corresponding View is present.
    $view = Views::getView('localgov_content_by_owner');
    if ($view instanceof ViewExecutable) {
      $links['localgov_workflows_notifications_content_by_owner'] = [
        'title' => $this->t('Content by owner'),
        'description' => $this->t('Table of contents, by owner.'),
        'route_name' => 'view.localgov_content_by_owner.page_1',
        'parent' => 'entity.localgov_service_contact.collection',
      ] + $base_plugin_definition;
    }

    return $links;
  }

}
