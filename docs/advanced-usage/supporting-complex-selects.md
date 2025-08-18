---
title: Supporting complex selects
sidebar_position: 1
---

Sometimes you might need to conditionally include some columns through some query scopes that might not be in the original database table.
A common scenario is including distance for coordinate fields when a user supplies a latitude and longitude.

For these scenarios, you can override the `modifyQuery` method to call these scopes.

```php
// IslandsController.php

protected function modifyQuery(\Spatie\QueryBuilder\QueryBuilder $query): \Spatie\QueryBuilder\QueryBuilder
{
    if (request()->has('lat') && request()->has('lng')) {
        if ($this->sorts()->has('distance') || $this->fields()->has('distance')) {
            $query->withDistance('islands.coordinates', new Point(request()->input('lat'), request()->input('lng'), Srid::WGS84));
        }
    }

    return $query;
}
```

Notice the calls to the `$this->sorts()` and `$this->fields()` methods. These are helper methods to retrieve the `sorts` and `fields` of the current `QueryBuilder` request.
The following helper methods are included in the Api controllers.

- `sorts()`
- `fields()`
- `includes()`
- `filters()`
- `appends()`

You can use the `modifyQuery` method to conditionally eager load relations as well to prevent N + 1 queries.

```php
protected function modifyQuery(\Spatie\QueryBuilder\QueryBuilder $query): \Spatie\QueryBuilder\QueryBuilder
{   
    $fields = $this->fields()->isNotEmpty() ? $this->fields()->get('_') : [];

    if ((is_array($fields) && in_array('formatted_name', $fields)) || $this->appends()->has('formatted_name')) {
        $query->with('atoll');
    }

    return $query;
}
```

The `$this->fields()->get('_')` will return the fields requested for the main model.

For complex selects, you might need to dynamically set the allowed fields and sorts as well.

```php
public function getAllowedFields(): array
{
    $fields = \Schema::getColumnListing('islands');

    if (request()->has('lat') && request()->has('lng')) {
        $fields[] = 'distance';
    }

    return array_diff($fields, (new Island)->getHidden());
}

public function getAllowedSorts(): array
{
    $sorts = [
        'id',
        'created_at',
        'updated_at',
        'name',
        'code',
        'land_survey_code',
        'order_column',
    ];
    
    if (request()->has('lat') && request()->has('lng')) {
        $sorts[] = 'distance';
    }
    
    return $sorts;
}
```
