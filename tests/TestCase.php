<?php

namespace Omure\ScoutAdvancedMeilisearch\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Omure\ScoutAdvancedMeilisearch\ScoutAdvancedMeilisearchServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ScoutAdvancedMeilisearchServiceProvider::class,
        ];
    }
}
