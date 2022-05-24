<?php

namespace Omure\ScoutAdvancedMeilisearch\Facades\MeiliSearch;

use App\Models\Interfaces\MeilisearchSearchableModel;
use MeiliSearch\Client;

class MeiliSearchService
{
    public function __construct(protected Client $meiliSearchClient)
    {

    }

    public function updateIndexSettings(MeilisearchSearchableModel $model)
    {
        $index = $this->meiliSearchClient->index($model->searchableAs());

        /**
         * Currently bugged.
         * https://docs.meilisearch.com/reference/api/searchable_attributes.html#update-searchable-attributes
         *
         * WARNING
         *
         * Due to an implementation bug, manually updating searchableAttributes
         * will change the displayed order of document fields in the
         * JSON response. This behavior is inconsistent and
         * will be fixed in a future release.
         */
        //$index->updateSearchableAttributes($model->getSearchableAttributes());
        $index->updateSortableAttributes($model->getSortableAttributes());
        $index->updateFilterableAttributes($model->getFilterableAttributes());
    }
}