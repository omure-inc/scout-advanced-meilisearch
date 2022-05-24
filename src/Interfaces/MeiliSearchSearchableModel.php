<?php

namespace Omure\ScoutAdvancedMeilisearch\Interfaces;

interface MeiliSearchSearchableModel
{
    public function getSearchableAttributes(): array;

    public function getFilterableAttributes(): array;

    public function getSortableAttributes(): array;

    public function toSearchableArray(): array;
}