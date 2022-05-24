<?php

namespace Omure\ScoutAdvancedMeilisearch\Engines;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\CollectionEngine;
use Omure\ScoutAdvancedMeilisearch\Exceptions\CollectionMeiliSearchException;
use Omure\ScoutAdvancedMeilisearch\Interfaces\MeiliSearchSearchableModel;

class CollectionMeiliSearchTestEngine extends CollectionEngine
{
    /**
     * @throws CollectionMeiliSearchException
     */
    protected function searchModels(Builder $builder): Collection
    {
        $this->checkQuery($builder);

        /** @var \Illuminate\Database\Query\Builder $query */
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

            $searchables = $model->toSearchableArray();

            if (count($builder->wheres) > 0) {
                foreach ($builder->wheres as $value) {
                    if (is_array($searchables[$value[0]])) {
                        $isFound = false;
                        foreach ($searchables[$value[0]] as $searchValue) {
                            if ($this->isValueFound($value[2], $value[1], $searchValue)) {
                                $isFound = true;
                                break;
                            }
                        }
                        if (!$isFound) {
                            return false;
                        }
                    } elseif (!$this->isValueFound($value[2], $value[1], $searchables[$value[0]])) {
                        return false;
                    }
                }
            }

            if (count($builder->whereIns) > 0) {
                foreach ($builder->whereIns as $key => $value) {
                    if (is_array($searchables[$key])) {
                        if (!array_intersect($value, $searchables[$key])) {
                            return false;
                        }
                    } elseif (!in_array($searchables[$key], $value)) {
                        return false;
                    }
                }
            }

            if (!$builder->query) {
                return true;
            }

            $searchableKeys = $model->getSearchableAttributes();

            foreach ($searchables as $key => $value) {
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

    protected function isValueFound($value, $operator, $compare): bool
    {
        return match ($operator) {
            '=' => $compare === $value,
            '!=' => $compare !== $value,
            '>' => is_numeric($value) && is_numeric($compare) && $compare > $value,
            '>=' => is_numeric($value) && is_numeric($compare) && $compare >= $value,
            '<' => is_numeric($value) && is_numeric($compare) && $compare < $value,
            '<=' => is_numeric($value) && is_numeric($compare) && $compare <= $value,
            default => false,
        };
    }

    /**
     * @throws CollectionMeiliSearchException
     */
    protected function checkQuery(Builder $builder)
    {
        $modelClass = get_class($builder->model);

        if (!$builder->model instanceof MeiliSearchSearchableModel) {
            throw new CollectionMeiliSearchException(
                "Model '$modelClass' does not implement MeiliSearchSearchableModel interface"
            );
        }

        $filterableKeys = $builder->model->getFilterableAttributes();

        $wheres = [];

        foreach ($builder->wheres as $where) {
            $wheres[] = $where[0];
        }

        $filteredKeys = array_unique(array_merge($wheres, array_keys($builder->whereIns)));

        $filterableDifference = array_diff($filteredKeys, $filterableKeys);

        if ($filterableDifference) {
            throw new CollectionMeiliSearchException(
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
            throw new CollectionMeiliSearchException(
                "Model '$modelClass' method getSortableAttributes() does not contain elements you're trying to sort by. " .
                'Fields: ' . json_encode($sortableDifference)
            );
        }
    }
}