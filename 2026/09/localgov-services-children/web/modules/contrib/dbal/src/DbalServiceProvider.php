<?php

declare(strict_types=1);

namespace Drupal\dbal;

use Doctrine\Persistence\ConnectionRegistry;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service provider for DBAL.
 */
final class DbalServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    if (interface_exists('\Doctrine\Persistence\ConnectionRegistry')) {
      $definition = (new Definition(DoctrineConnectionRegistry::class))
        ->addArgument(['default' => new Reference('dbal_connection')])
        // Private service: Use autowiring or the service alias if you need,
        // e.g: \Drupal::service(ConnectionRegistry::class);.
        ->setPublic(FALSE);

      $anonymousHash = ContainerBuilder::hash(DoctrineConnectionRegistry::class . mt_rand());
      $container->setDefinition('.' . $anonymousHash, $definition);

      $container->setAlias(ConnectionRegistry::class, '.' . $anonymousHash);
    }
  }

}
