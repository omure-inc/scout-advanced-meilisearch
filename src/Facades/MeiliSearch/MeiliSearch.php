<?php

namespace Omure\ScoutAdvancedMeilisearch\Facades\MeiliSearch;

use Illuminate\Support\Facades\Facade;
use Omure\ScoutAdvancedMeilisearch\Interfaces\MeiliSearchSearchableModel;

/**
 * @method static void updateIndexSettings(MeiliSearchSearchableModel $model)
 */
class MeiliSearch extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'meiliSearch';
    }
}
