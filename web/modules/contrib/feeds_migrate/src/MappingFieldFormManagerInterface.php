<?php

namespace Drupal\feeds_migrate;

use Drupal\feeds_migrate\Entity\MigrationInterface;

/**
 * Interface MappingFieldFormManagerInterface.
 *
 * @package Drupal\feeds_migrate
 */
interface MappingFieldFormManagerInterface {

  /**
   * Get the plugin ID from the field type.
   *
   * @param array $mapping
   *   The field type being migrated.
   *
   * @return string
   *   The ID of the plugin for the field_type if available.
   */
  public function getPluginIdFromMapping(array $mapping);

  /**
   * Creates a pre-configured instance of a migration plugin.
   *
   * A specific createInstance method is necessary to pass the migration on.
   *
   * @param string $plugin_id
   *   The ID of the plugin being instantiated.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   * @param \Drupal\feeds_migrate\Entity\MigrationInterface $migration
   *   The migration context in which the plugin will run.
   *
   * @return \Drupal\feeds_migrate\MappingFieldFormInterface
   *   A fully configured plugin instance.
   */
  public function createInstance($plugin_id, array $configuration = [], MigrationInterface $migration = NULL);

}
