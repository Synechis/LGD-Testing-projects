<?php

namespace Drupal\localgov_workflows_notifications\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Provides an entity reference selection for service contacts.
 *
 * @EntityReferenceSelection(
 *   id = "service_contact_reference",
 *   label = @Translation("Service Contacts"),
 *   entity_types = {"localgov_service_contact"},
 *   group = "service_contact_reference",
 *   weight = 0
 * )
 */
class ServiceContactReference extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS'): QueryInterface {
    $query = $this->entityTypeManager->getStorage('localgov_service_contact')->getQuery('OR');

    if (isset($match)) {
      $query->condition('name', $match, $match_operator);
      $query->condition('email', $match, $match_operator);
      $query->condition('user.entity.name', $match, $match_operator);
      $query->condition('user.entity.mail', $match, $match_operator);
    }

    // Add the Selection handler for system_query_entity_reference_alter().
    $query->addTag('entity_reference');
    $query->addMetaData('entity_reference_selection_handler', $this);
    $query->accessCheck(TRUE);

    return $query;
  }

}
