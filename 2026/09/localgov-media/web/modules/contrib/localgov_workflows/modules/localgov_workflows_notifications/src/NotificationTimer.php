<?php

namespace Drupal\localgov_workflows_notifications;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\State\StateInterface;

/**
 * Handle timing when to send notifications.
 */
class NotificationTimer implements NotificationTimerInterface {

  /**
   * Module settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $settings;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * Constructs a NotificationTimer object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, TimeInterface $time) {
    $this->settings = $config_factory->get('localgov_workflows_notifications.settings');
    $this->state = $state;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastRun(): ?int {
    return $this->state->get(self::LAST_RUN);
  }

  /**
   * {@inheritdoc}
   */
  public function reset(): void {
    $this->state->set(self::LAST_RUN, 0);
  }

  /**
   * {@inheritdoc}
   */
  public function update(): void {
    $request_time = $this->time->getRequestTime();
    $this->state->set(self::LAST_RUN, $request_time);
  }

  /**
   * {@inheritdoc}
   */
  public function trigger(): bool {
    $request_time = $this->time->getRequestTime();
    $last_run = $this->state->get(self::LAST_RUN, $request_time);
    $next_run = $last_run + 86400 * $this->settings->get('email_frequency');
    if ($request_time >= $next_run) {
      return TRUE;
    }

    return FALSE;
  }

}
