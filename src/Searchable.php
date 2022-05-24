<?php

namespace Omure\ScoutAdvancedMeilisearch;

use Laravel\Scout\Searchable as ScoutSearchable;

trait Searchable
{
    use ScoutSearchable;

    public static function search($query = '', $callback = null): Builder
    {
        return app(Builder::class, [
            'model' => new static(),
            'query' => $query,
            'callback' => $callback,
            'softDelete'=> static::usesSoftDelete() && config('scout.soft_delete', false),
        ]);
    }
}