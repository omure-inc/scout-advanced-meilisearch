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

    private function formatToString(BuilderWhere $where): string
    {
        $value = $where->value;

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (!is_numeric($value)) {
            $value = '"' . $value . '"';
        }

        return "$where->connector $where->field $where->operator $value";
    }
}
