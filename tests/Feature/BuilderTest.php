<?php

namespace Omure\ScoutAdvancedMeilisearch\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Omure\ScoutAdvancedMeilisearch\Builder;
use Omure\ScoutAdvancedMeilisearch\BuilderWhere;
use Omure\ScoutAdvancedMeilisearch\Exceptions\BuilderException;
use Omure\ScoutAdvancedMeilisearch\Tests\TestCase;

class BuilderTest extends TestCase
{
    public function test_where_two_arguments()
    {
        /** @var Builder $builder */
        $builder = $this->partialMock(Builder::class);

        $builder->where('field_a', 'value_a');
        $builder->where('field_b', 'value_b');

        $this->assertCount(2, $builder->wheres);
        $this->assertEquals(new BuilderWhere('field_a', '=', 'value_a', 'AND'), $builder->wheres[0]);
        $this->assertEquals(new BuilderWhere('field_b', '=', 'value_b', 'AND'), $builder->wheres[1]);
    }

    public function test_where_three_arguments()
    {
        /** @var Builder $builder */
        $builder = $this->partialMock(Builder::class);

        $builder->where('field_a', '=', 'value_a');
        $builder->where('field_b', '!=','value_b');
        $builder->where('field_c', '>','value_c');
        $builder->where('field_d', '>=','value_d');
        $builder->where('field_e', '<','value_e');
        $builder->where('field_f', '<=','value_f');

        $this->assertCount(6, $builder->wheres);
        $this->assertEquals(new BuilderWhere('field_a', '=', 'value_a', 'AND'), $builder->wheres[0]);
        $this->assertEquals(new BuilderWhere('field_b', '!=', 'value_b', 'AND'), $builder->wheres[1]);
        $this->assertEquals(new BuilderWhere('field_c', '>', 'value_c', 'AND'), $builder->wheres[2]);
        $this->assertEquals(new BuilderWhere('field_d', '>=', 'value_d', 'AND'), $builder->wheres[3]);
        $this->assertEquals(new BuilderWhere('field_e', '<', 'value_e', 'AND'), $builder->wheres[4]);
        $this->assertEquals(new BuilderWhere('field_f', '<=', 'value_f', 'AND'), $builder->wheres[5]);
    }

    public function test_where_three_arguments_with_wrong_operator()
    {
        /** @var Builder $builder */
        $builder = $this->partialMock(Builder::class);

        $this->expectException(BuilderException::class);
        $this->expectExceptionMessage('Operator =< is not allowed. Allowed operators: =,!=,>,>=,<,<=.');

        $builder->where('field_a', '=<', 'value_a');
    }

    public function test_or_where_two_arguments()
    {
        /** @var Builder $builder */
        $builder = $this->partialMock(Builder::class);

        $builder->orWhere('field_a', 'value_a');
        $builder->orWhere('field_b', 'value_b');

        $this->assertCount(2, $builder->wheres);
        $this->assertEquals(new BuilderWhere('field_a', '=', 'value_a', 'OR'), $builder->wheres[0]);
        $this->assertEquals(new BuilderWhere('field_b', '=', 'value_b', 'OR'), $builder->wheres[1]);
    }

    public function test_or_where_three_arguments()
    {
        /** @var Builder $builder */
        $builder = $this->partialMock(Builder::class);

        $builder->orWhere('field_a', '=', 'value_a');
        $builder->orWhere('field_b', '!=','value_b');
        $builder->orWhere('field_c', '>','value_c');
        $builder->orWhere('field_d', '>=','value_d');
        $builder->orWhere('field_e', '<','value_e');
        $builder->orWhere('field_f', '<=','value_f');

        $this->assertCount(6, $builder->wheres);
        $this->assertEquals(new BuilderWhere('field_a', '=', 'value_a', 'OR'), $builder->wheres[0]);
        $this->assertEquals(new BuilderWhere('field_b', '!=', 'value_b', 'OR'), $builder->wheres[1]);
        $this->assertEquals(new BuilderWhere('field_c', '>', 'value_c', 'OR'), $builder->wheres[2]);
        $this->assertEquals(new BuilderWhere('field_d', '>=', 'value_d', 'OR'), $builder->wheres[3]);
        $this->assertEquals(new BuilderWhere('field_e', '<', 'value_e', 'OR'), $builder->wheres[4]);
        $this->assertEquals(new BuilderWhere('field_f', '<=', 'value_f', 'OR'), $builder->wheres[5]);
    }

    public function test_or_where_three_arguments_with_wrong_operator()
    {
        /** @var Builder $builder */
        $builder = $this->partialMock(Builder::class);

        $this->expectException(BuilderException::class);
        $this->expectExceptionMessage('Operator =< is not allowed. Allowed operators: =,!=,>,>=,<,<=.');

        $builder->orWhere('field_a', '=<', 'value_a');
    }

    public function test_where_closure()
    {
        /** @var Model $mockModel */
        $mockModel = $this->mock(Model::class);

        $builder = new Builder($mockModel, 'query');

        $builder->where(function (Builder $query) {
            $query
                ->where('field_a', 1)
                ->where('field_b', 2)
                ->whereIn('field_c', [3, 4]);
        });

        $this->assertCount(1, $builder->wheres);
        $this->assertInstanceOf(Builder::class, $builder->wheres[0]->field);

        $wheres = $builder->wheres[0]->field->wheres;

        $this->assertEquals(new BuilderWhere('field_a', '=', 1, 'AND'), $wheres[0]);
        $this->assertEquals(new BuilderWhere('field_b', '=', 2, 'AND'), $wheres[1]);

        $this->assertInstanceOf(Builder::class, $wheres[2]->field);

        $wheresIn = $wheres[2]->field->wheres;

        $this->assertEquals(new BuilderWhere('field_c', '=', 3, 'OR'), $wheresIn[0]);
        $this->assertEquals(new BuilderWhere('field_c', '=', 4, 'OR'), $wheresIn[1]);
    }

    public function test_where_between()
    {
        /** @var Builder $builder */
        $builder = $this->partialMock(Builder::class);

        $builder->whereBetween('field_a', [1, 2]);
        $builder->whereBetween('field_b', ['3', '4']);

        $this->assertCount(4, $builder->wheres);
        $this->assertEquals(new BuilderWhere('field_a', '>=', 1, 'AND'), $builder->wheres[0]);
        $this->assertEquals(new BuilderWhere('field_a', '<=', 2, 'AND'), $builder->wheres[1]);
        $this->assertEquals(new BuilderWhere('field_b', '>=', '3', 'AND'), $builder->wheres[2]);
        $this->assertEquals(new BuilderWhere('field_b', '<=', '4', 'AND'), $builder->wheres[3]);
    }

    public function test_where_between_with_wrong_values_count()
    {
        /** @var Builder $builder */
        $builder = $this->partialMock(Builder::class);

        $this->expectException(BuilderException::class);
        $this->expectExceptionMessage('whereBetween values array requires exactly two elements.');

        $builder->whereBetween('field_a', [1, 2, 3]);
    }

    public function test_where_between_with_wrong_values_types()
    {
        /** @var Builder $builder */
        $builder = $this->partialMock(Builder::class);

        $this->expectException(BuilderException::class);
        $this->expectExceptionMessage('whereBetween values array should contain only numeric elements.');

        $builder->whereBetween('field_a', [1, 'string_value']);
    }

    public function test_where_in()
    {
        /** @var Model $mockModel */
        $mockModel = $this->mock(Model::class);

        $builder = new Builder($mockModel, 'query');

        $builder->whereIn('field_a', [1, 2, 3]);

        $nestedWheres = $builder->wheres[0]->field->wheres;

        $this->assertCount(1, $builder->wheres);
        $this->assertEquals('AND', $builder->wheres[0]->connector);
        $this->assertCount(3, $nestedWheres);
        $this->assertEquals(new BuilderWhere('field_a', '=', 1, 'OR'), $nestedWheres[0]);
        $this->assertEquals(new BuilderWhere('field_a', '=', 2, 'OR'), $nestedWheres[1]);
        $this->assertEquals(new BuilderWhere('field_a', '=', 3, 'OR'), $nestedWheres[2]);
    }

    public function test_where_not_in()
    {
        /** @var Builder $builder */
        $builder = $this->partialMock(Builder::class);

        $builder->whereNotIn('field_a', [1]);
        $builder->whereNotIn('field_b', [2, 3, 4]);

        $this->assertCount(4, $builder->wheres);
        $this->assertEquals(new BuilderWhere('field_a', '!=', 1, 'AND'), $builder->wheres[0]);
        $this->assertEquals(new BuilderWhere('field_b', '!=', 2, 'AND'), $builder->wheres[1]);
        $this->assertEquals(new BuilderWhere('field_b', '!=', 3, 'AND'), $builder->wheres[2]);
        $this->assertEquals(new BuilderWhere('field_b', '!=', 4, 'AND'), $builder->wheres[3]);
    }
}
