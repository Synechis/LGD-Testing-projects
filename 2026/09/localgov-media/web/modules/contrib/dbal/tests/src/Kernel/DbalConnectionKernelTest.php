<?php

declare(strict_types=1);

namespace Drupal\Tests\dbal\Kernel;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests dbal_connection service.
 *
 * @group dbal
 */
class DbalConnectionKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'dbal'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    // We use the semaphore table, which is created by acquiring a lock.
    $this->container->get('lock.persistent')->acquire('dbal_test');
    $this->container->get('lock.persistent')->release('dbal_test');
  }

  /**
   * Tests dbal_connection service and factory.
   */
  public function testConnectionFactory() {
    $database = $this->container->get('database');
    $connection = $this->container->get('dbal_connection');
    $connection->insert($database->getFullQualifiedTableName('semaphore'),
      [
        'name' => 'dbal_test',
        'value' => 'dbal_test',
        'expire' => time(),
      ]);
    $this->assertEquals('dbal_test', $database->select('semaphore', 's')
      ->condition('name', 'dbal_test')
      ->fields('s', ['value'])
      ->execute()
      ->fetchField());
  }

  /**
   * Tests accessing private connection registry service via auto-wiring alias.
   */
  public function testConnectionRegistry(): void {
    // Accessing private connection registry service via auto-wiring alias.
    /** @var \Doctrine\Persistence\ConnectionRegistry $connectionRegistry */
    $connectionRegistry = \Drupal::service(ConnectionRegistry::class);

    $this->assertEquals(['default'], $connectionRegistry->getConnectionNames());
    $this->assertEquals('default', $connectionRegistry->getDefaultConnectionName());

    $connections = $connectionRegistry->getConnections();
    $this->assertCount(1, $connections);
    $this->assertInstanceOf(Connection::class, $connections['default']);

    $defaultConnection = $connectionRegistry->getConnection();
    $this->assertInstanceOf(Connection::class, $defaultConnection);

    $defaultConnection = $connectionRegistry->getConnection('default');
    $this->assertInstanceOf(Connection::class, $defaultConnection);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Missing foobar connection');
    $connectionRegistry->getConnection('foobar');
  }

}
