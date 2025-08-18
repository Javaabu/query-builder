---
title: Upgrade Guide
sidebar_position: 1.3
---

## Migration from v2 to v3

In v3, the access level of the `ApiBaseController` abstract methods have been changed from `protected` to `public`.

```php
// In v2

/**
 * Get the allowed sorts
 *
 * @return array
 */
protected function getAllowedSorts(): array
{
    return [
        'id',
        'name',
        'slug',
        'created_at',
        'updated_at',
    ];
}

// In v3

/**
 * Get the allowed sorts
 *
 * @return array
 */
public function getAllowedSorts(): array
{
    return [
        'id',
        'name',
        'slug',
        'created_at',
        'updated_at',
    ];
}
```

So, you will need to change the access level of the following methods of your API controllers.


```php
getBaseQuery(): Builder;
getAllowedFields(): array;
getAllowedIncludes(): array;
getAllowedAppends(): array;
getAllowedSorts(): array;
getDefaultSort(): string;
getAllowedFilters(): array;
getShowAllowedAppends(): array
getAllShowAllowedAppends(): array
getShowAllowedAppendAttributes(): array
getAllowedAppendAttributes(): array
getIndexAllowedFields(): array
getFieldsToAlwaysInclude(): array
allowUnlimitedResultsPerPage(): bool
```
