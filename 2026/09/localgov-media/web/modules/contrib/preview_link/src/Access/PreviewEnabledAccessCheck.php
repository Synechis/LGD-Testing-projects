<?php

declare(strict_types=1);

namespace Drupal\preview_link\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\preview_link\EntityTypeBundleChecker;
use Symfony\Component\Routing\Route;

/**
 * Preview link access check.
 */
class PreviewEnabledAccessCheck implements AccessInterface {

  /**
   * PreviewEnabledAccessCheck constructor.
   */
  public function __construct(protected EntityTypeBundleChecker $entityTypeBundleChecker) {
  }

  /**
   * Checks access to both the generate route and the preview route.
   */
  public function access(Route $route, RouteMatchInterface $route_match): AccessResultInterface {
    // Get the entity for both the preview route and the generate preview link
    // route.
    $entity = match($route->getOption('preview_link.entity_type_id')) {
      NULL => $route_match->getParameter('entity'),
      default => $route_match->getParameter($route->getOption('preview_link.entity_type_id')),
    };

    return AccessResult::allowedIf($this->entityTypeBundleChecker->entityTypeAndBundleEnabled($entity))
      ->addCacheableDependency($entity)
      ->addCacheContexts(['route'])
      ->addCacheableDependency($this->entityTypeBundleChecker->getConfig());
  }

}
