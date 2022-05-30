<?php

namespace Omure\ScoutAdvancedMeilisearch;

class BuilderWhere
{
    public function __construct(
        public string|Builder $field,
        public string|null $operator = '=',
        public mixed $value = null,
        public string $connector = 'AND',
    ) {}
}