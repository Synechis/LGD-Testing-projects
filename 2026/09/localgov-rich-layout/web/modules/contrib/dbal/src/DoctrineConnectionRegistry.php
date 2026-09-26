<?php

declare(strict_types=1);

namespace Drupal\dbal;

use Doctrine\Persistence\ConnectionRegistry;

/**
 * Doctrine Connection Registry.
 *
 * Service is only present when doctrine/persistence library is detected.
 *
 * @see \Drupal\dbal\DbalServiceProvider
 */
final class DoctrineConnectionRegistry implements ConnectionRegistry {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    private array $connections,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConnectionName(): string {
    return array_key_first($this->connections);
  }

  /**
   * {@inheritdoc}
   */
  public function getConnection(?string $name = NULL): object {
    if ($name === NULL) {
      return $this->connections[array_key_first($this->connections)] ?? throw new \LogicException('Missing default connection');
    }

    return $this->connections[$name] ?? throw new \Exception(sprintf('Missing %s connection', $name));
  }

  /**
   * {@inheritdoc}
   */
  public function getConnections(): array {
    return $this->connections;
  }

  /**
   * {@inheritdoc}
   */
  public function getConnectionNames(): array {
    return array_keys($this->connections);
  }

}
