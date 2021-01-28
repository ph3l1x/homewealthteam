<?php

namespace Drupal\Tests\feeds_migrate\Traits;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Provides methods for mocking certain classes.
 *
 * This trait is meant to be used only by test classes.
 */
trait FeedsMockingTrait {

  /**
   * Returns a mocked field definition.
   *
   * @param string $field_name
   *   The field's name.
   * @param string[] $properties
   *   The field's property names.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The mocked field definition.
   */
  protected function getMockedFieldDefinition($field_name, array $properties) {
    $storage_field_definition = $this->prophesize(FieldStorageDefinitionInterface::class);
    $storage_field_definition->getPropertyNames()->willReturn($properties);
    $storage_field_definition->getMainPropertyName()->willReturn(reset($properties));

    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getName()->willReturn($field_name);
    $field_definition->getFieldStorageDefinition()->willReturn($storage_field_definition->reveal());

    return $field_definition;
  }

  /**
   * Returns mocked field definitions for the given fields.
   *
   * @param array $properties_per_field
   *   The fields to create definitions for, keyed by field name.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The mocked field definitions.
   */
  protected function getMockedFieldDefinitions(array $properties_per_field) {
    $field_definitions = [];
    foreach ($properties_per_field as $field_name => $properties) {
      $field_definitions[$field_name] = $this->getMockedFieldDefinition($field_name, $properties)->reveal();
    }

    return $field_definitions;
  }

}
