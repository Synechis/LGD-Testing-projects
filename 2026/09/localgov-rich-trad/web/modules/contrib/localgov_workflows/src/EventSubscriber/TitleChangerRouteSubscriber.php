<?php

declare(strict_types=1);

namespace Drupal\localgov_workflows\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Change the title of some routes.
 */
class TitleChangerRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {

    foreach ($collection as $name => $route) {
      if (str_ends_with($name, '.scheduled_transition_add')) {
        $route->setDefault('_title', 'Add schedule');
      }
      elseif ($name == 'entity.scheduled_transition.reschedule_form') {
        $route->setDefault('_title', 'Reschedule');
      }
    }
  }

}
