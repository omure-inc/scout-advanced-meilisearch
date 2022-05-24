<?php

namespace Omure\ScoutAdvancedMeilisearch\Engines;

use Laravel\Scout\Builder;
use Laravel\Scout\Engines\MeiliSearchEngine;

class MeiliSearchExtendedEngine extends MeiliSearchEngine
{
    protected function filters(Builder $builder): string
    {
        $filters = collect($builder->wheres)->map(function ($value) {
            if (is_bool($value[2])) {
                return sprintf('%s %s %s', $value[0], $value[1], $value[2] ? 'true' : 'false');
            }

            return is_numeric($value[2])
                ? sprintf('%s %s %s', $value[0], $value[1], $value[2])
                : sprintf('%s %s "%s"', $value[0], $value[1], $value[2]);
        });

        foreach ($builder->whereIns as $key => $values) {
            $filters->push(sprintf('(%s)', collect($values)->map(function ($value) use ($key) {
                if (is_bool($value)) {
                    return sprintf('%s = %s', $key, $value ? 'true' : 'false');
                }

                return filter_var($value, FILTER_VALIDATE_INT) !== false
                    ? sprintf('%s = %s', $key, $value)
                    : sprintf('%s = "%s"', $key, $value);
            })->values()->implode(' OR ')));
        }

        return $filters->values()->implode(' AND ');
    }
}
