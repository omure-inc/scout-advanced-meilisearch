# Scout Advanced Meilisearch

## What this package provides

### Extended scout query builder
Added next changes to the basic query builder:
1) ```where('column', '<=', $value)``` - extended where, which supports 3 optional parameters for comparison. 
Two parameters are also allowed.
2) ```where(Clusure $query)``` - where can take a closure as the first parameter in order to group queries (the same for ```orWhere```)
3) ```whereBetween('column', [$value1, $value2])```
4) ```whereNotIn('column', [$value1, $value2, $value3])```
5) All where clauses have or variant: ```orWhere```, ```orWhereIn```, etc

### Two scout drivers

The drivers are compatible with the new scout builder.

- meilisearch_advanced
- collection_advanced

#### meilisearch_advanced
Uses all the advantages of meilisearch for comparison the results, searching in indexed arrays.
Also fixes an issue with the calculation of the total number of values. 
(The current implementation requests the entire dataset in case scout builder has ```->query()``` method used)

#### collection_advanced
The driver imitates how meilisearch work and completely relies on collections. It should be used
only for tests as the whole searchable models data is in memory.
The driver allows testing filtering and sorting as well as Extended scout builder features.


### Meilisearch specific are taken into account
Meilisearch requires updating indexes filterable, sortable and searchable attributes in order to save the indexing data properly.
This package provides ```MeiliSearch``` facade that has ```updateIndexSettings``` method that handles that automatically.

## How to use?

1) In ```config/scout.php``` set ```'driver'``` to ```meilisearch_advanced``` or ```collection_advanced```
2) Apply Searchable trait to the model (```Omure\ScoutAdvancedMeilisearch\Searchable```);
3) For proper handling of the model, implement ```Omure\ScoutAdvancedMeilisearch\Interfaces\MeiliSearchSearchableModel``` interface to the model
4) Describe indexed parameters for searching in ```toSearchableArray()``` method of the model (the same way as Scout does)
5) Specify searchable, filterable and sortable attributes by defining the next list of methods on the model:
- ```getSearchableAttributes()``` - for using ```search()``` statements
- ```getFilterableAttributes()``` - for using ```where()```, including ```whereIn```, ```whereNotIn```, etc. statements
- ```getSortableAttributes()``` - for using ```orderBy()``` statement
- ```getTypoToleranceSettings()``` - for typo tolerance
All methods should return an array of strings which are the names of the parameters specified in ```toSearchableArray()```.

If you change the methods ```getSearchableAttributes()```, ```getFilterableAttributes()```, or ```getSortableAttributes()```
returned indexes, you have to let meilisearch know about the changes. For that purpose use the facade:
```MeiliSearch::updateIndexSettings(new User());```

The method takes a model instance to update the parameters on the meilisearch server.

Keep in mind that ```toSearchableArray()``` doesn't have to have the same indexes as the database column names.
You can specify your own logic for searching and filtering. Use the specified parameters in your Scout Builder.


