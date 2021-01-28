<?php

namespace Drupal\Tests\feeds_migrate\Unit\Entity;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds_migrate\Entity\Migration;
use Drupal\Tests\feeds_migrate\Unit\FeedsMigrateUnitTestBase;

/**
 * @coversDefaultClass \Drupal\feeds_migrate\Entity\Migration
 * @group feeds_migrate
 */
class MigrationTest extends FeedsMigrateUnitTestBase {

  /**
   * Field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->fieldManager = $this->prophesize(EntityFieldManagerInterface::class);
  }

  /**
   * Creates a new migration entity.
   *
   * @param array $values
   *   (optional) The values for the migration.
   *
   * @return \Drupal\feeds_migrate\Entity\Migration
   *   A migration entity.
   */
  protected function createMigration(array $values = []) {
    $migration = new Migration($values, 'migration');
    $migration->setFieldManager($this->fieldManager->reveal());

    return $migration;
  }

  /**
   * @covers ::getEntityTypeIdFromDestination
   *
   * @param string $expected_entity_type_id
   *   The expected entity type ID.
   * @param array $values
   *   The values to set on the migration.
   *
   * @dataProvider providerMigrationWithEntityDestination
   */
  public function testGetEntityTypeIdFromDestination($expected_entity_type_id, array $values) {
    $migration = $this->createMigration($values);

    $entity_type_id = $migration->getEntityTypeIdFromDestination();
    $this->assertEquals($expected_entity_type_id, $entity_type_id);
  }

  /**
   * Data provider for testGetEntityTypeIdFromDestination().
   */
  public function providerMigrationWithEntityDestination() {
    return [
      'term' => [
        'expected_entity_type_id' => 'taxonomy_term',
        'values' => [
          'destination' => [
            'plugin' => 'entity:taxonomy_term',
          ],
        ],
      ],
      'nothing' => [
        'expected_entity_type_id' => NULL,
        'values' => [
          'destination' => [
            'plugin' => 'config',
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::getEntityBundleFromDestination
   *
   * @param string $expected_bundle
   *   The expected bundle name.
   * @param array $values
   *   The values to set on the migration.
   *
   * @dataProvider providerMigrationWithEntityDestinationAndBundle
   */
  public function testGetEntityBundleFromDestination($expected_bundle, array $values) {
    $migration = $this->createMigration($values);

    $bundle = $migration->getEntityBundleFromDestination();
    $this->assertEquals($expected_bundle, $bundle);
  }

  /**
   * Data provider for testGetEntityBundleFromDestination().
   */
  public function providerMigrationWithEntityDestinationAndBundle() {
    $test_cases = [
      'node' => [
        'expected_bundle' => 'article',
        'values' => [
          'destination' => [
            'plugin' => 'entity:node',
            'default_bundle' => 'article',
          ],
        ],
      ],
      'node_bundle_as_constant' => [
        'expected_bundle' => 'menu_link_content',
        'values' => [
          'source' => [
            'plugin' => 'menu_link',
            'constants' => [
              'bundle' => 'menu_link_content',
            ],
          ],
          'destination' => [
            'plugin' => 'entity:menu_link_content',
            'no_stub' => 'true',
          ],
        ],
      ],
      'node_bundle_as_default_value' => [
        'expected_bundle' => 'migrate_example_beer',
        'values' => [
          'process' => [
            'type' => [
              'plugin' => 'default_value',
              'default_value' => 'migrate_example_beer',
            ],
          ],
          'destination' => [
            'plugin' => 'entity:taxonomy_term',
          ],
        ],
      ],
      'term_bundle_as_default_value' => [
        'expected_bundle' => 'migrate_example_beer_styles',
        'values' => [
          'process' => [
            'vid' => [
              'plugin' => 'default_value',
              'default_value' => 'migrate_example_beer_styles',
            ],
          ],
          'destination' => [
            'plugin' => 'entity:taxonomy_term',
          ],
        ],
      ],
    ];

    // @todo support these cases as well.
    unset($test_cases['node_bundle_as_default_value']);
    unset($test_cases['term_bundle_as_default_value']);

    return $test_cases;
  }

  /**
   * @covers ::getDestinationField
   */
  public function testGetDestinationField() {
    $foo = $this->createMock(FieldDefinitionInterface::class);
    $bar = $this->createMock(FieldDefinitionInterface::class);

    $this->fieldManager->getFieldDefinitions('node', 'article')
      ->willReturn([
        'foo' => $foo,
        'bar' => $bar,
      ]);
    $migration = $this->createMigration([
      'destination' => [
        'plugin' => 'entity:node',
        'default_bundle' => 'article',
      ],
    ]);

    $this->assertSame($foo, $migration->getDestinationField('foo'));
    $this->assertSame($bar, $migration->getDestinationField('bar'));
    $this->assertNull($migration->getDestinationField('non_existent'));
  }

  /**
   * @covers ::getDestinationFields
   */
  public function testGetDestinationFields() {
    $foo = $this->createMock(FieldDefinitionInterface::class);
    $bar = $this->createMock(FieldDefinitionInterface::class);

    $this->fieldManager->getFieldDefinitions('node', 'article')
      ->willReturn([
        'foo' => $foo,
        'bar' => $bar,
      ]);
    $migration = $this->createMigration([
      'destination' => [
        'plugin' => 'entity:node',
        'default_bundle' => 'article',
      ],
    ]);

    $expected = [
      'foo' => $foo,
      'bar' => $bar,
    ];
    $this->assertSame($expected, $migration->getDestinationFields());
  }

  /**
   * Tests getting mappings.
   *
   * @param array $expected_mappings
   *   The expected mappings after converting process lines.
   * @param array $process
   *   The process lines.
   *
   * @covers ::getMappings
   * @covers ::initializeMappings
   * @covers ::normalizeProcessConfig
   *
   * @dataProvider providerMigrationWithProcess
   */
  public function testGetMappings(array $expected_mappings, array $process) {
    // Make sure properties are known for all fields.
    $properties_per_field = [
      'title' => ['value'],
      'body' => ['value', 'summary', 'format'],
      'field_alpha' => ['target_id'],
      'field_beta' => ['value'],
    ];
    $field_definitions = $this->getMockedFieldDefinitions($properties_per_field);

    $this->fieldManager->getFieldDefinitions('node', 'article')
      ->willReturn($field_definitions);

    $migration = $this->createMigration([
      'process' => $process,
      'destination' => [
        'plugin' => 'entity:node',
        'default_bundle' => 'article',
      ],
    ]);

    $this->assertEquals($expected_mappings, $migration->getMappings());
  }

  /**
   * Data provider for testGetMappings().
   */
  public function providerMigrationWithProcess() {
    return [
      'empty' => [
        'expected_mappings' => [],
        'process' => [],
      ],
      'simple-get' => [
        'expected_mappings' => [
          'foo' => [
            'destination' => [
              'key' => 'foo',
            ],
            'source' => 'bar',
            'process' => [],
          ],
          'qux' => [
            'destination' => [
              'key' => 'qux',
            ],
            'source' => 'baz',
            'process' => [],
          ],
        ],
        'process' => [
          'foo' => 'bar',
          'qux' => 'baz',
        ],
      ],
      'extensive-get' => [
        'expected_mappings' => [
          'title' => [
            'value' => [
              'destination' => [
                'key' => 'title',
              ],
              'source' => 'source_title',
              'process' => [],
            ],
          ],
          'body' => [
            'value' => [
              'destination' => [
                'key' => 'body',
                'property' => 'value',
              ],
              'source' => 'body',
              'process' => [],
            ],
            'summary' => [
              'destination' => [
                'key' => 'body',
                'property' => 'summary',
              ],
              'source' => 'summary',
              'process' => [],
            ],
          ],
        ],
        'process' => [
          'title' => [
            'plugin' => 'get',
            'source' => 'source_title',
          ],
          'body/value' => [
            'plugin' => 'get',
            'source' => 'body',
          ],
          'body/summary' => [
            'plugin' => 'get',
            'source' => 'summary',
          ],
        ],
      ],
      'with-process-plugins' => [
        'expected_mappings' => [
          'title' => [
            'value' => [
              'destination' => [
                'key' => 'title',
              ],
              'source' => '',
              'process' => [
                [
                  'plugin' => 'default_value',
                  'default_value' => 'foo',
                ],
              ],
            ],
          ],
          'field_alpha' => [
            'target_id' => [
              'destination' => [
                'key' => 'field_alpha',
              ],
              'source' => 'terms',
              'process' => [
                [
                  'plugin' => 'migration_lookup',
                  'migration' => 'beer_term',
                ],
              ],
            ],
          ],
          'field_beta' => [
            'value' => [
              'destination' => [
                'key' => 'field_beta',
              ],
              'source' => 'name',
              'process' => [
                [
                  'plugin' => 'callback',
                  'callable' => 'mb_strtolower',
                ],
                [
                  'plugin' => 'callback',
                  'callable' => 'ucwords',
                ],
              ],
            ],
          ],
        ],
        'process' => [
          'title' => [
            'plugin' => 'default_value',
            'default_value' => 'foo',
          ],
          'field_alpha' => [
            'plugin' => 'migration_lookup',
            'migration' => 'beer_term',
            'source' => 'terms',
          ],
          'field_beta' => [
            [
              'plugin' => 'callback',
              'callable' => 'mb_strtolower',
              'source' => 'name',
            ],
            [
              'plugin' => 'callback',
              'callable' => 'ucwords',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::setMapping
   */
  public function testSetMapping() {
    $migration = $this->createMigration([
      'process' => [],
      'destination' => [
        'plugin' => 'entity:node',
        'default_bundle' => 'article',
      ],
    ]);

    $this->assertEquals($migration, $migration->setMapping('foo', [
      'destination' => [
        'key' => 'foo',
      ],
      'source' => 'bar',
      'process' => [],
    ]));

    $expected_process = [
      'foo' => 'bar',
    ];
    $this->assertEquals($expected_process, $migration->get('process'));
  }

  /**
   * Tests setting mappings.
   *
   * @param array $expected_process
   *   The expected process lines after setting mapping.
   * @param array $mappings
   *   The mappings being set.
   *
   * @covers ::setMapping
   * @covers ::setMappings
   * @covers ::convertMappingToProcess
   *
   * @dataProvider providerMappings
   */
  public function testSetMappings(array $expected_process, array $mappings) {
    // Make sure properties are known for all fields.
    $properties_per_field = [
      'title' => ['value'],
      'body' => ['value', 'summary', 'format'],
      'field_alpha' => ['target_id'],
      'field_beta' => ['value'],
    ];
    $field_definitions = $this->getMockedFieldDefinitions($properties_per_field);

    $this->fieldManager->getFieldDefinitions('node', 'article')
      ->willReturn($field_definitions);

    $migration = $this->createMigration([
      'process' => [],
      'destination' => [
        'plugin' => 'entity:node',
        'default_bundle' => 'article',
      ],
    ]);
    $this->assertEquals($migration, $migration->setMappings($mappings));

    $this->assertEquals($expected_process, $migration->get('process'));
  }

  /**
   * Data provider for testSetMappings().
   */
  public function providerMappings() {
    $return = [];
    // Reverse the params from ::providerMigrationWithProcess().
    foreach ($this->providerMigrationWithProcess() as $case => $params) {
      $return[$case] = [
        'expected_process' => $params['process'],
        'mappings' => $params['expected_mappings'],
      ];
    }

    // Change some cases.
    $return['extensive-get']['expected_process'] = [
      'title' => 'source_title',
      'body/value' => 'body',
      'body/summary' => 'summary',
    ];

    return $return;
  }

  /**
   * Tests overwrite mappings.
   *
   * Needs to happen when setting mappings in a different order.
   *
   * @covers ::setMapping
   * @covers ::setMappings
   * @covers ::convertMappingToProcess
   */
  public function testOverwriteMappings() {
    // Make sure properties are known for all fields.
    $properties_per_field = [
      'title' => ['value'],
      'body' => ['value', 'summary', 'format'],
      'field_alpha' => ['target_id'],
      'field_beta' => ['value'],
    ];
    $field_definitions = $this->getMockedFieldDefinitions($properties_per_field);

    $this->fieldManager->getFieldDefinitions('node', 'article')
      ->willReturn($field_definitions);

    // Create a migration with some process lines.
    $migration = $this->createMigration([
      'process' => [
        'title' => [
          'plugin' => 'get',
          'source' => 'source_title',
        ],
        'body/value' => [
          'plugin' => 'get',
          'source' => 'body',
        ],
        'field_beta' => [
          [
            'plugin' => 'callback',
            'callable' => 'mb_strtolower',
            'source' => 'name',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:node',
        'default_bundle' => 'article',
      ],
    ]);

    // Make sure mappings are calculated so that there's something to overwrite.
    $migration->getMappings();

    // Define mappings:
    // - body is put first.
    // - body/summary is added.
    // - field_beta is removed.
    $mappings = [
      'body' => [
        'value' => [
          'destination' => [
            'key' => 'body',
            'property' => 'value',
          ],
          'source' => 'body',
          'process' => [],
        ],
        'summary' => [
          'destination' => [
            'key' => 'body',
            'property' => 'summary',
          ],
          'source' => 'summary',
          'process' => [],
        ],
      ],
      'title' => [
        'value' => [
          'destination' => [
            'key' => 'title',
          ],
          'source' => 'source_title',
          'process' => [],
        ],
      ],
    ];

    $expected_process = [
      'body/value' => 'body',
      'body/summary' => 'summary',
      'title' => 'source_title',
    ];

    $this->assertSame($migration, $migration->setMappings($mappings));
    $this->assertSame($mappings, $migration->getMappings());
    $this->assertSame($expected_process, $migration->get('process'));
  }

  /**
   * @covers ::removeMapping
   *
   * @param array $expected_process
   *   The expected process lines after removing mapping.
   * @param array $expected_mappings
   *   The expected mappings after removing mapping.
   * @param array $process
   *   The process lines.
   * @param string $remove
   *   The destination key of the mapping to remove.
   *
   * @dataProvider providerRemoveMapping
   */
  public function testRemoveMapping(array $expected_process, array $expected_mappings, array $process, $remove) {
    // Make sure properties are known for all fields.
    $properties_per_field = [
      'title' => ['value'],
      'body' => ['value', 'summary', 'format'],
      'field_alpha' => ['target_id'],
      'field_beta' => ['value'],
    ];
    $field_definitions = $this->getMockedFieldDefinitions($properties_per_field);

    $this->fieldManager->getFieldDefinitions('node', 'article')
      ->willReturn($field_definitions);

    $migration = $this->createMigration([
      'process' => $process,
      'destination' => [
        'plugin' => 'entity:node',
        'default_bundle' => 'article',
      ],
    ]);
    $migration->getMappings();
    $this->assertEquals($migration, $migration->removeMapping($remove));

    $this->assertEquals($expected_process, $migration->get('process'));
    $this->assertEquals($expected_mappings, $migration->getMappings());
  }

  /**
   * Data provider for ::testRemoveMapping().
   */
  public function providerRemoveMapping() {
    $cases = [];

    // Borrow the params from ::providerMigrationWithProcess().
    foreach ($this->providerMigrationWithProcess() as $case => $params) {
      $cases[$case] = [
        'expected_process' => $params['process'],
        'expected_mappings' => $params['expected_mappings'],
        'process' => $params['process'],
        'remove' => '',
      ];
    }

    // For the first one, we remove just some mapper that doesn't exist.
    $cases['empty']['remove'] = 'foo';

    // For the simple-get case, remove mapping to 'foo'.
    $cases['simple-get']['remove'] = 'foo';
    unset($cases['simple-get']['expected_process']['foo']);
    unset($cases['simple-get']['expected_mappings']['foo']);

    // For the extensive-get case, remove mapping to 'body'.
    $cases['extensive-get']['remove'] = 'body';
    unset($cases['extensive-get']['expected_process']['body/value']);
    unset($cases['extensive-get']['expected_process']['body/summary']);
    unset($cases['extensive-get']['expected_mappings']['body']);

    // For the with-process-plugins case, remove mapping to 'field_alpha'.
    $cases['with-process-plugins']['remove'] = 'field_alpha';
    unset($cases['with-process-plugins']['expected_process']['field_alpha']);
    unset($cases['with-process-plugins']['expected_mappings']['field_alpha']);

    return $cases;
  }

  /**
   * @covers ::removeMappings
   *
   * @param array $expected_process
   *   The expected process lines after removing mapping.
   * @param array $expected_mappings
   *   The expected mappings after removing mapping.
   * @param array $process
   *   The process lines.
   *
   * @dataProvider providerRemoveMappings
   */
  public function testRemoveMappings(array $expected_process, array $expected_mappings, array $process) {
    // Make sure properties are known for all fields.
    $properties_per_field = [
      'title' => ['value'],
      'body' => ['value', 'summary', 'format'],
      'field_alpha' => ['target_id'],
      'field_beta' => ['value'],
    ];
    $field_definitions = $this->getMockedFieldDefinitions($properties_per_field);

    $this->fieldManager->getFieldDefinitions('node', 'article')
      ->willReturn($field_definitions);

    $migration = $this->createMigration([
      'process' => $process,
      'destination' => [
        'plugin' => 'entity:node',
        'default_bundle' => 'article',
      ],
    ]);
    $this->assertEquals($migration, $migration->removeMappings());

    $this->assertEquals($expected_process, $migration->get('process'));
    $this->assertEquals($expected_mappings, $migration->getMappings());
  }

  /**
   * Data provider for ::testRemoveMappings().
   */
  public function providerRemoveMappings() {
    $cases = [];

    // Borrow the params from ::providerMigrationWithProcess().
    // For these cases we expect process and mappings to become empty.
    foreach ($this->providerMigrationWithProcess() as $case => $params) {
      $cases[$case] = [
        'expected_process' => [],
        'expected_mappings' => [],
        'process' => $params['process'],
      ];
    }

    return $cases;
  }

}
