<?php

namespace Omure\ScoutAdvancedMeilisearch\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Mockery\MockInterface;
use Omure\ScoutAdvancedMeilisearch\Builder;
use Omure\ScoutAdvancedMeilisearch\Engines\CollectionMeiliSearchTestEngine;
use Omure\ScoutAdvancedMeilisearch\Tests\TestCase;

class CollectionDriverTest extends TestCase
{
    public function test_model_is_not_searchable()
    {
        /** @var Builder $builder */
        $builder = $this->partialMock(Builder::class);

        /** @var Model $model */
        $model = $this->partialMock(Model::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive('shouldBeSearchable')
                ->andReturn(false);
        });

        /** @var CollectionMeiliSearchTestEngine $engine */
        $engine = $this->partialMock(CollectionMeiliSearchTestEngine::class);

        $this->assertFalse($engine->isFound($builder, $model));
    }

    public function test_no_wheres_no_queries()
    {
        /** @var Builder $builder */
        $builder = $this->partialMock(Builder::class);

        $builder->wheres = [];
        $builder->query = '';

        /** @var Model $model */
        $model = $this->partialMock(Model::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive('shouldBeSearchable')
                ->andReturn(true);

            $mock
                ->shouldReceive('toSearchableArray')
                ->andReturn([]);
        });

        /** @var CollectionMeiliSearchTestEngine $engine */
        $engine = $this->partialMock(CollectionMeiliSearchTestEngine::class);

        $this->assertTrue($engine->isFound($builder, $model));
    }

    public function test_text_search()
    {
        /** @var Builder $builder */
        $builder = $this->partialMock(Builder::class);

        $builder->wheres = [];

        /** @var Model $model */
        $model = $this->partialMock(Model::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive('shouldBeSearchable')
                ->andReturn(true);

            $mock
                ->shouldReceive('toSearchableArray')
                ->andReturn([
                    'field_a' => '.,abc',
                    'field_b' => 'defgh,.',
                    'field_c' => 'ijklm',
                ]);

            $mock
                ->shouldReceive('getSearchableAttributes')
                ->andReturn(['field_a', 'field_b']);
        });

        /** @var CollectionMeiliSearchTestEngine $engine */
        $engine = $this->partialMock(CollectionMeiliSearchTestEngine::class);

        $builder->query = 'abc';
        $this->assertTrue($engine->isFound($builder, $model));

        $builder->query = 'a.b,c.';
        $this->assertTrue($engine->isFound($builder, $model));

        $builder->query = 'defgh';
        $this->assertTrue($engine->isFound($builder, $model));

        $builder->query = 'ijklm';
        $this->assertFalse($engine->isFound($builder, $model));
    }

    public function test_filters()
    {
        /** @var Model $model */
        $model = $this->partialMock(Model::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive('shouldBeSearchable')
                ->andReturn(true);

            $mock
                ->shouldReceive('getSearchableAttributes')
                ->andReturn([]);

            $mock
                ->shouldReceive('toSearchableArray')
                ->andReturn([
                    'field_a' => 'abc',
                    'field_b' => 2,
                    'field_c' => 3,
                    'field_d' => null,
                    'field_e' => [5, 6, 7],
                ]);
        });

        $builder = new Builder($model, '');
        $builder->wheres = [];

        /** @var CollectionMeiliSearchTestEngine $engine */
        $engine = $this->partialMock(CollectionMeiliSearchTestEngine::class);

        $builder->wheres = [];
        $builder->where('field_a', 'abc');
        $this->assertTrue($engine->isFound($builder, $model));

        $builder->wheres = [];
        $builder
            ->where('field_a', 'abc')
            ->where('field_b', '!=', 3);
        $this->assertTrue($engine->isFound($builder, $model));

        $builder->wheres = [];
        $builder
            ->where('field_a', 'abc')
            ->where('field_b', 3);
        $this->assertFalse($engine->isFound($builder, $model));

        $builder->wheres = [];
        $builder
            ->where('field_a', 'abcd')
            ->orWhere('field_b', 3);
        $this->assertFalse($engine->isFound($builder, $model));

        $builder->wheres = [];
        $builder
            ->where('field_a', 'abc')
            ->orWhere('field_b', 3);
        $this->assertTrue($engine->isFound($builder, $model));

        $builder->wheres = [];
        $builder
            ->where('field_a', 'abcd')
            ->orWhere('field_b', 2);
        $this->assertTrue($engine->isFound($builder, $model));

        $builder->wheres = [];
        $builder
            ->where(function (Builder $query) {
                $query
                    ->where('field_d', null)
                    ->orWhere('field_a', 'd');
            });
        $this->assertTrue($engine->isFound($builder, $model));

        $builder->wheres = [];
        $builder
            ->where(function (Builder $query) {
                $query
                    ->where('field_d', 1)
                    ->orWhere('field_a', 'd');
            });
        $this->assertFalse($engine->isFound($builder, $model));

        $builder->wheres = [];
        $builder->whereIn('field_b', [2, 3, 4]);
        $this->assertTrue($engine->isFound($builder, $model));

        $builder->wheres = [];
        $builder->whereIn('field_b', [3, 4, 5]);
        $this->assertFalse($engine->isFound($builder, $model));

        $builder->wheres = [];
        $builder->where('field_e', 6);
        $this->assertTrue($engine->isFound($builder, $model));

        $builder->wheres = [];
        $builder->where('field_e', 8);
        $this->assertFalse($engine->isFound($builder, $model));
    }
}
