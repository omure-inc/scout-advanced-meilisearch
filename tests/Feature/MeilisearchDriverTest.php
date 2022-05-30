<?php

namespace Omure\ScoutAdvancedMeilisearch\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Omure\ScoutAdvancedMeilisearch\Builder;
use Omure\ScoutAdvancedMeilisearch\Engines\MeiliSearchExtendedEngine;
use Omure\ScoutAdvancedMeilisearch\Tests\TestCase;

class MeilisearchDriverTest extends TestCase
{
    public function test_filters()
    {
        /** @var Model $mockModel */
        $mockModel = $this->mock(Model::class);

        $builder = new Builder($mockModel, 'query');

        /** @var MeiliSearchExtendedEngine $engine */
        $engine = $this->partialMock(MeiliSearchExtendedEngine::class);

        $this->assertEquals('', $engine->filters($builder));

        $builder->where('field_a', '!=', 25)
            ->orWhere('field_b', 15);

        $this->assertEquals('field_a != 25 OR field_b = 15', $engine->filters($builder));

        $builder->where(function(Builder $query) {
            $query->where('field_c', '>=', 14)
                ->orWhere('field_d', '!=', 34);
        });

        $this->assertEquals('field_a != 25 OR field_b = 15 AND (field_c >= 14 OR field_d != 34)', $engine->filters($builder));
    }
}
