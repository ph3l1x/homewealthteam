<?php

namespace Drupal\Tests\feeds_migrate\Unit;

use Drupal\Tests\feeds_migrate\Traits\FeedsMockingTrait;
use Drupal\Tests\feeds_migrate\Traits\FeedsReflectionTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Base class for Feeds Migrate unit tests.
 */
abstract class FeedsMigrateUnitTestBase extends UnitTestCase {

  use FeedsMockingTrait;
  use FeedsReflectionTrait;

}
