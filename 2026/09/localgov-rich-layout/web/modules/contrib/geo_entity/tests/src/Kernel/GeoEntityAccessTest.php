<?php

namespace Drupal\Tests\geo_entity\Kernel;

use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the access permissions for editing geo entities.
 *
 * @group geo_entity
 */
class GeoEntityAccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'token',
    'filter',
    'geofield',
    'geo_entity',
    'datetime',
  ];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = TRUE;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A superuser with all permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $superUser;

  /**
   * A user with 'edit own geo' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editOwnUser;

  /**
   * A user with 'edit any geo' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editAnyUser;

  /**
   * A user with 'delete own geo' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $deleteOwnUser;

  /**
   * A user with 'delete any geo' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $deleteAnyUser;

  /**
   * A user with 'administer geo' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user with only 'view geo' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $noPermissionUser;

  /**
   * A geo entity owned by the first user.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $firstUserGeo;

  /**
   * A geo entity owned by the second user.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $secondUserGeo;

  /**
   * A geo entity owned by the deleteOwnUser.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $deleteOwnUserGeo;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('geo_entity');
    $this->installEntitySchema('geo_entity_type');
    $this->container->get('database')->schema()->createTable('sequences', [
      'fields' => [
        'value' => [
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['value'],
    ]);

    // Install necessary configuration for token module and other dependencies.
    $this->installConfig([
      'system',
      'user',
      'field',
      'text',
      'datetime',
      'filter',
      'token',
    ]);

    // Get the entity type manager service.
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Create a geo entity type.
    $geo_type_storage = $this->entityTypeManager->getStorage('geo_entity_type');
    $geo_type = $geo_type_storage->create([
      'id' => 'test_type',
      'label' => 'Test Type',
      'label_token' => '[geo_entity:id]',
    ]);
    $geo_type->save();

    // Create test users with different permissions.
    $this->superUser = $this->createUser([], 'super user', TRUE);
    $this->editOwnUser = $this->createUser(['edit own geo', 'view geo'], 'edit own geo', FALSE);
    $this->editAnyUser = $this->createUser(['edit any geo', 'view geo'], 'edit any geo', FALSE);
    $this->deleteOwnUser = $this->createUser(['delete own geo', 'view geo'], 'delete own geo', FALSE);
    $this->deleteAnyUser = $this->createUser(['delete any geo', 'view geo'], 'delete any geo', FALSE);
    $this->adminUser = $this->createUser(['administer geo', 'view geo'], 'administer geo', TRUE);

    // Create a user with no permissions for testing.
    $this->noPermissionUser = $this->createUser(['view geo'], 'no permission', FALSE);

    // Create geo entities owned by different users.
    $this->firstUserGeo = $this->entityTypeManager->getStorage('geo_entity')->create([
      'bundle' => 'test_type',
      'label' => 'Geo owned by editOwnUser',
      'uid' => $this->editOwnUser->id(),
    ]);
    $this->firstUserGeo->save();

    $this->secondUserGeo = $this->entityTypeManager->getStorage('geo_entity')->create([
      'bundle' => 'test_type',
      'label' => 'Geo owned by editAnyUser',
      'uid' => $this->editAnyUser->id(),
    ]);
    $this->secondUserGeo->save();

    // Create a geo entity owned by the deleteOwnUser for testing delete permissions.
    $geo_storage = $this->entityTypeManager->getStorage('geo_entity');
    $this->deleteOwnUserGeo = $geo_storage->create([
      'bundle' => 'test_type',
      'label' => 'Geo owned by deleteOwnUser',
      'uid' => $this->deleteOwnUser->id(),
    ]);
    $this->deleteOwnUserGeo->save();
  }

  /**
   * Tests access to edit geo entities based on ownership and permissions.
   */
  public function testEditAccess() {
    $access_handler = $this->entityTypeManager->getAccessControlHandler('geo_entity');

    // Test edit access for a user with 'edit own geo' permission.
    $this->assertTrue(
      $access_handler->access($this->firstUserGeo, 'update', $this->editOwnUser),
      'Owner with "edit own geo" permission should be able to edit their own geo entity.'
    );

    // Create a separate entity owned by a different user to test access denial.
    $other_user_geo = $this->entityTypeManager->getStorage('geo_entity')->create([
      'bundle' => 'test_type',
      'label' => 'Geo owned by another user',
      'uid' => $this->noPermissionUser->id(),
    ]);
    $other_user_geo->save();

    // Verify the owner IDs are different.
    $this->assertNotEquals(
      $this->editOwnUser->id(),
      $other_user_geo->getOwnerId(),
      'The test entity should be owned by a different user.'
    );

    // Test access directly with the entity.
    $this->assertFalse(
      $access_handler->access($other_user_geo, 'update', $this->editOwnUser),
      'User with "edit own geo" permission should not be able to edit someone else\'s geo entity.'
    );

    // Test edit access for a user with 'edit any geo' permission.
    $this->assertTrue(
      $access_handler->access($this->secondUserGeo, 'update', $this->editAnyUser),
      'User with "edit any geo" permission should be able to edit their own geo entity.'
    );

    $this->assertTrue(
      $access_handler->access($this->firstUserGeo, 'update', $this->editAnyUser),
      'User with "edit any geo" permission should be able to edit someone else\'s geo entity.'
    );

    // Test edit access for admin user.
    $this->assertTrue(
      $access_handler->access($this->firstUserGeo, 'update', $this->adminUser),
      'Admin user should be able to edit any geo entity.'
    );

    $this->assertTrue(
      $access_handler->access($this->secondUserGeo, 'update', $this->adminUser),
      'Admin user should be able to edit any geo entity.'
    );
  }

  /**
   * Tests access to delete geo entities based on ownership and permissions.
   */
  public function testDeleteAccess() {
    $access_handler = $this->entityTypeManager->getAccessControlHandler('geo_entity');

    // Test delete access for a user with 'delete own geo' permission.
    $this->assertTrue(
      $access_handler->access($this->deleteOwnUserGeo, 'delete', $this->deleteOwnUser),
      'Owner with "delete own geo" permission should be able to delete their own geo entity.'
    );

    $this->assertFalse(
      $access_handler->access($this->secondUserGeo, 'delete', $this->deleteOwnUser),
      'User with "delete own geo" permission should not be able to delete someone else\'s geo entity.'
    );

    // Test delete access for a user with 'delete any geo' permission.
    $this->assertTrue(
      $access_handler->access($this->firstUserGeo, 'delete', $this->deleteAnyUser),
      'User with "delete any geo" permission should be able to delete any geo entity.'
    );

    $this->assertTrue(
      $access_handler->access($this->secondUserGeo, 'delete', $this->deleteAnyUser),
      'User with "delete any geo" permission should be able to delete any geo entity.'
    );

    // Test delete access for admin user.
    $this->assertTrue(
      $access_handler->access($this->firstUserGeo, 'delete', $this->adminUser),
      'Admin user should be able to delete any geo entity.'
    );

    $this->assertTrue(
      $access_handler->access($this->secondUserGeo, 'delete', $this->adminUser),
      'Admin user should be able to delete any geo entity.'
    );
  }

  /**
   * Tests that a user without permissions cannot edit or delete entities.
   */
  public function testNoPermissions() {
    $access_handler = $this->entityTypeManager->getAccessControlHandler('geo_entity');

    // Use the user with no edit/delete permissions.
    // Test that user cannot edit any geo entity.
    $this->assertFalse(
      $access_handler->access($this->firstUserGeo, 'update', $this->noPermissionUser),
      'User without edit permissions should not be able to edit any geo entity.'
    );
    $this->assertFalse(
      $access_handler->access($this->secondUserGeo, 'update', $this->noPermissionUser),
      'User without edit permissions should not be able to edit any geo entity.'
    );

    // Test that user cannot delete any geo entity.
    $this->assertFalse(
      $access_handler->access($this->firstUserGeo, 'delete', $this->noPermissionUser),
      'User without delete permissions should not be able to delete any geo entity.'
    );
    $this->assertFalse(
      $access_handler->access($this->secondUserGeo, 'delete', $this->noPermissionUser),
      'User without delete permissions should not be able to delete any geo entity.'
    );
  }

}
