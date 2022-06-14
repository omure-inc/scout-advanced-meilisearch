<?php

namespace Omure\ScoutAdvancedMeilisearch\Facades\MeiliSearch;

use MeiliSearch\Client;
use Omure\ScoutAdvancedMeilisearch\Interfaces\MeiliSearchSearchableModel;

class MeiliSearchService
{
    public function __construct(protected Client $meiliSearchClient)
    {

    }

    public function updateIndexSettings(MeilisearchSearchableModel $model)
    {
        $index = $this->meiliSearchClient->index($model->searchableAs());

        $index->updateSearchableAttributes($model->getSearchableAttributes());
        $index->updateSortableAttributes($model->getSortableAttributes());
        $index->updateFilterableAttributes($model->getFilterableAttributes());
    }
}