<?php

namespace Drupal\localgov_workflows_notifications\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates local tasks.
 */
class LocalgovWorkflowsNotificationsLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $this->derivatives = [];
    // Only create the local task if the corresponding View is present.
    $view = Views::getView('localgov_content_by_owner');
    if ($view instanceof ViewExecutable) {
      $this->derivatives['localgov_workflows_notifications.contacts'] = $base_plugin_definition;
      $this->derivatives['localgov_workflows_notifications.contacts']['route_name'] = 'entity.localgov_service_contact.collection';
      $this->derivatives['localgov_workflows_notifications.contacts']['base_route'] = 'entity.localgov_service_contact.collection';
      $this->derivatives['localgov_workflows_notifications.contacts']['parent_id'] = 'entity.localgov_service_contact.collection';
      $this->derivatives['localgov_workflows_notifications.contacts']['title'] = $this->t('Contacts');

      $this->derivatives['localgov_workflows_notifications.content'] = $base_plugin_definition;
      $this->derivatives['localgov_workflows_notifications.content']['route_name'] = 'view.localgov_content_by_owner.page_1';
      $this->derivatives['localgov_workflows_notifications.content']['parent_id'] = 'entity.localgov_service_contact.collection';
      $this->derivatives['localgov_workflows_notifications.content']['base_route'] = 'entity.localgov_service_contact.collection';
      $this->derivatives['localgov_workflows_notifications.content']['title'] = $this->t('Content');
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
