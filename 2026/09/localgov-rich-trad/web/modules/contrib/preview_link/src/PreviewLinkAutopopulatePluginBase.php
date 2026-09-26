<?php

declare(strict_types=1);

namespace Drupal\preview_link;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\preview_link\Entity\PreviewLink;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for preview_link_autopopulate plugins.
 */
abstract class PreviewLinkAutopopulatePluginBase extends PluginBase implements PreviewLinkAutopopulateInterface, ContainerFactoryPluginInterface {

  /**
   * The entity being previewed.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface|null
   */
  protected ?ContentEntityInterface $entity = NULL;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a preview_link_autopopulate plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;

    // Get the entity being previewed.
    $entityParameterName = $route_match->getRouteObject()->getOption('preview_link.entity_type_id');
    if (!is_null($entityParameterName)) {
      $this->entity = $route_match->getParameter($entityParameterName);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): ?ContentEntityInterface {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(ContentEntityInterface $entity): static {
    $this->entity = $entity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup|string {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getPreviewEntities(): array;

  /**
   * {@inheritdoc}
   */
  public function isSupported(): bool {
    if (!is_null($this->entity)) {
      $supported_entities = $this->pluginDefinition['supported_entities'];
      $type = $this->entity->getEntityTypeId();
      if (isset($supported_entities[$type])) {

        // Check if all bundles are supported.
        $supported_bundles = $supported_entities[$type];
        if (empty($supported_bundles)) {
          return TRUE;
        }

        // Check if given bundle is supported.
        elseif (in_array($this->entity->bundle(), $supported_bundles)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Populate entities in preview link.
   *
   * @param \Drupal\preview_link\Entity\PreviewLink $preview_link
   *   The preview link to be populated.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function populatePreviewLinks(PreviewLink $preview_link): void {
    if (is_null($this->entity)) {
      return;
    }

    // Get all entities to be previewed.
    $entities = $this->getPreviewEntities();

    // Add entities to preview link.
    $current_entities = $preview_link->getEntities();
    foreach ($entities as $entity) {
      $found = FALSE;
      foreach ($current_entities as $current_entity) {
        if ($current_entity === $entity) {
          $found = TRUE;
          break;
        }
      }
      if (!$found) {
        $preview_link->addEntity($entity);
      }
    }
    $preview_link->save();
  }

}
