<?php

namespace Omure\ScoutAdvancedMeilisearch;

use Laravel\Scout\Builder as LaravelScoutBuilder;

class Builder extends LaravelScoutBuilder
{
    public function where($field, $value, $valueWithOperator = null): static
    {
        $appliedOperator = $valueWithOperator ? $value : '=';
        $appliedValue = $valueWithOperator ?: $value;

        $this->wheres[] = [$field, $appliedOperator, $appliedValue];

        return $this;
    }

    public function whereBetween($field, array $values): static
    {
        $this->wheres[] = [$field, '>=', $values[0]];
        $this->wheres[] = [$field, '<=', $values[1] ?? 0];

        return $this;
    }

    public function whereNotIn($field, array $values): static
    {
        foreach ($values as $value) {
            $this->wheres[] = [$field, '!=', $value];
        }

        return $this;
    }

    protected function getTotalCount($results): int
    {
        $engine = $this->engine();
        return $engine->getTotalCount($results);
    }
}