<?php

namespace Drupal\localgov_workflows_notifications;

/**
 * Interface for handling notification timing.
 */
interface NotificationTimerInterface {

  /**
   * Last notification state variable name.
   *
   * @var string
   */
  const LAST_RUN = 'localgov_workflows_notifications.last_email_run';

  /**
   * Get the last time notifications were triggered.
   *
   * @return int|null
   *   Timestamp of last notification.
   */
  public function getLastRun(): ?int;

  /**
   * Reset the notification timer.
   */
  public function reset(): void;

  /**
   * Update the notification timer.
   */
  public function update(): void;

  /**
   * Is it time to trigger notification sending?
   */
  public function trigger(): bool;

}
