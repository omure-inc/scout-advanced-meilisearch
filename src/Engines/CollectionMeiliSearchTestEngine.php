<?php

namespace Omure\ScoutAdvancedMeilisearch\Engines;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Laravel\Scout\Builder as ScoutBuilder;
use Laravel\Scout\Engines\CollectionEngine;
use Illuminate\Database\Query\Builder as DatabaseQueryBuilder;
use Omure\ScoutAdvancedMeilisearch\Builder;
use Omure\ScoutAdvancedMeilisearch\BuilderWhere;
use Omure\ScoutAdvancedMeilisearch\Exceptions\BuilderException;
use Omure\ScoutAdvancedMeilisearch\Interfaces\MeiliSearchSearchableModel;

class CollectionMeiliSearchTestEngine extends CollectionEngine
{
    /**
     * @throws BuilderException
     */
    protected function searchModels(ScoutBuilder $builder): Collection
    {
        $this->checkQuery($builder);

        /** @var DatabaseQueryBuilder $query */
        $query = $builder->model->query()->orderBy($builder->model->getKeyName(), 'desc');

        $models = $this->ensureSoftDeletesAreHandled($builder, $query)
            ->get()
            ->values();

        if (count($models) === 0) {
            return $models;
        }

        $models = $models->filter(function ($model) use ($builder) {
            if (!$model->shouldBeSearchable()) {
                return false;
            }

            $searchable = $model->toSearchableArray();

            if (count($builder->wheres)) {
                if (!$this->checkConditions($builder->wheres, $searchable)) {
                    return false;
                }
            }

            if (!$builder->query) {
                return true;
            }

            $searchableKeys = $model->getSearchableAttributes();

            foreach ($searchable as $key => $value) {
                if (!in_array($key, $searchableKeys)) {
                    return false;
                }

                if (!is_scalar($value)) {
                    $value = json_encode($value);
                }

                $modifiedValue = Str::lower(str_replace(['.', ','], '', $value));
                $modifiedQuery = Str::lower(str_replace(['.', ','], '', $builder->query));

                if (Str::contains($modifiedValue, $modifiedQuery)) {
                    return true;
                }
            }

            return false;
        });

        if ($builder->orders) {
            $models = $models->sortBy(
                collect($builder->orders)->map(function (array $order) {
                    return [$order['column'], $order['direction']];
                })->toArray()
            );
        }

        return $models->values();
    }
    
    protected function checkConditions(array $wheres, array $searchable): bool
    {
        $conditions = collect($wheres)->map(function (BuilderWhere $where) use ($searchable) {
            return [
                'result' => $where->field instanceof Builder ? 
                    $this->checkConditions($where->field->wheres, $searchable) : 
                    $this->isValueFound($where, $searchable),
                'connector' => $where->connector,
            ];
        });
        
        return $this->executeConditions($conditions);
    }

    protected function isValueFound(BuilderWhere $where, mixed $model): bool
    {
        $modelValue = $model[$where->field];

        return match ($where->operator) {
            '=' => is_array($modelValue) ? in_array($where->value, $modelValue) : $modelValue === $where->value,
            '!=' => is_array($modelValue) ? !in_array($where->value, $modelValue) : $modelValue !== $where->value,
            '>' => is_numeric($modelValue) && is_numeric($where->value) && $modelValue > $where->value,
            '>=' => is_numeric($modelValue) && is_numeric($where->value) && $modelValue >= $where->value,
            '<' => is_numeric($modelValue) && is_numeric($where->value) && $modelValue < $where->value,
            '<=' => is_numeric($modelValue) && is_numeric($where->value) && $modelValue <= $where->value,
            default => false,
        };
    }
    
    protected function executeConditions(array $conditions): bool
    {
        $previousResult = null;

        $andsResults = [];

        foreach ($conditions as $condition) {
            if (is_null($previousResult)) {
                $previousResult = $condition['result'];
                continue;
            }

            if ($condition['connector'] === 'OR') {
                $andsResults[] = $previousResult;
                if ($previousResult) {
                    return true;
                }

                $previousResult = $condition['result'];
                continue;
            }

            $previousResult = $condition['result'] && $previousResult;
        }

        $andsResults[] = $previousResult;
        
        return in_array(true, $andsResults);
    }
    
    /**
     * @throws BuilderException
     */
    protected function checkQuery(ScoutBuilder $builder)
    {
        $modelClass = get_class($builder->model);

        if (!$builder->model instanceof MeiliSearchSearchableModel) {
            throw new BuilderException(
                "Model '$modelClass' does not implement MeiliSearchSearchableModel interface"
            );
        }

        $filterableKeys = $builder->model->getFilterableAttributes();

        $wheres = [];

        foreach ($builder->wheres as $where) {
            /** @var BuilderWhere $where */
            $wheres[] = $where->field;
        }

        $filteredKeys = array_unique(array_merge($wheres, array_keys($builder->whereIns)));

        $filterableDifference = array_diff($filteredKeys, $filterableKeys);

        if ($filterableDifference) {
            throw new BuilderException(
                "Model '$modelClass' method getFilterableAttributes() does not contain elements you're trying to filter. " .
                'Fields: ' . json_encode($filterableDifference)
            );
        }

        $sortableKeys = $builder->model->getSortableAttributes();
        $sortedKeys = collect($builder->orders)->map(function (array $order) {
            return $order['column'];
        })->toArray();

        $sortableDifference = array_diff($sortedKeys, $sortableKeys);

        if ($sortableDifference) {
            throw new BuilderException(
                "Model '$modelClass' method getSortableAttributes() does not contain elements you're trying to sort by. " .
                'Fields: ' . json_encode($sortableDifference)
            );
        }
    }
}