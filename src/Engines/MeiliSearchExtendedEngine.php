<?php

namespace Omure\ScoutAdvancedMeilisearch\Engines;

use Laravel\Scout\Builder as ScoutBuilder;
use Laravel\Scout\Engines\MeiliSearchEngine;
use Omure\ScoutAdvancedMeilisearch\Builder;
use Omure\ScoutAdvancedMeilisearch\BuilderWhere;

class MeiliSearchExtendedEngine extends MeiliSearchEngine
{
    public function filters(ScoutBuilder $builder): string
    {
        $filters = collect($builder->wheres)->map(function (BuilderWhere $where) {
            if ($where->field instanceof Builder) {
                return $where->connector . ' (' . $this->filters($where->field) . ')';
            }

            return $this->formatToString($where);
        });

        $filters = $filters->values()->implode(' ');
        $filters = ltrim($filters, 'AND ');
        return ltrim($filters, 'OR ');
    }

    public function update($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $index = $this->meilisearch->index($models->first()->searchableAs());

        if ($this->usesSoftDelete($models->first()) && $this->softDelete) {
            $models->each->pushSoftDeleteMetadata();
        }

        $objects = $models->map(function ($model) {
            if (empty($searchableData = $model->toSearchableArray())) {
                return;
            }

            $searchableData = $this->replaceNullsWithStrings($searchableData);

            return array_merge(
                [$model->getKeyName() => $model->getScoutKey()],
                $searchableData,
                $model->scoutMetadata()
            );
        })->filter()->values()->all();

        if (! empty($objects)) {
            $index->addDocuments($objects, $models->first()->getKeyName());
        }
    }

    public function replaceNullsWithStrings(array $array): array
    {
        return collect($array)->map(function ($element) {
            if (is_null($element)) {
                return 'null';
            }

            if (is_array($element)) {
                return $this->replaceNullsWithStrings($element);
            }

            return $element;
        })->toArray();
    }

    private function formatToString(BuilderWhere $where): string
    {
        $value = $where->value;

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            $value = '"null"';
        } elseif (!is_numeric($value)) {
            $value = '"' . $value . '"';
        }

        return "$where->connector $where->field $where->operator $value";
    }
}
