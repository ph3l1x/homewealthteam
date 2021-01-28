<?php

namespace Drupal\feeds_migrate;

use Drupal\feeds_migrate\Entity\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Manages migration entity helpers.
 *
 * @package Drupal\feeds_migrate
 * @deprecated \Drupal\feeds_migrate\MigrationEntityHelperManager is deprecated
 * and will be removed once it is no longer used by Feeds Migrate.
 */
class MigrationEntityHelperManager implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * @var \Drupal\feeds_migrate\MigrationEntityHelper[]
   */
  protected $migrations = [];

  /**
   * Gets the MigrationEntityHelper instance for a given Migration Entity.
   *
   * @param \Drupal\feeds_migrate\Entity\MigrationInterface $migration
   *
   * @return \Drupal\feeds_migrate\MigrationEntityHelper
   */
  public function get(MigrationInterface $migration) {
    $id = $migration->id();

    if (!isset($this->migrations[$id])) {
      $this->migrations[$id] = MigrationEntityHelper::create($this->container, $migration);
    }

    return $this->migrations[$id];
  }

}
