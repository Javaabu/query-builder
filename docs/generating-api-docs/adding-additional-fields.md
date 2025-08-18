---
title: Adding additional fields and query parameters
sidebar_position: 3
---

This package won't automatically detect dynamically allowed fields and sorts. 
For those, you will need to define them as additional allowed methods by overriding the following methods.

## apiDocAdditionalIndexQueryParameters()

Use this method to specify additional query parameters for `index` endpoint.
Will support all properties allowed by `Knuckles\Scribe\Attributes\QueryParam` Attribute.

```php
// in Islands.php
public static function apiDocAdditionalIndexQueryParameters(): array
{
    return [
        'lat' => [
            'type' => 'number',
            'description' => 'Latitude to calculate distance from.',
            'example' => 4.174446111,
        ],

        'lng' => [
            'type' => 'number',
            'description' => 'Longitude to calculate distance from.',
            'example' => 73.5097,
        ],
    ];
}
```

## apiDocAdditionalShowQueryParameters()

Use this method to specify additional query parameters for `show` endpoint.
Will support all properties allowed by `Knuckles\Scribe\Attributes\QueryParam` Attribute.

```php
// in Islands.php
public static function apiDocAdditionalShowQueryParameters(): array
{
    return [
        'lat' => [
            'type' => 'number',
            'description' => 'Latitude to calculate distance from.',
            'example' => 4.174446111,
        ],

        'lng' => [
            'type' => 'number',
            'description' => 'Longitude to calculate distance from.',
            'example' => 73.5097,
        ],
    ];
}
```

## apiDocAdditionalIndexAllowedFields()

Use this method to specify additional fields that can be selected for the `index` endpoint.


## apiDocAdditionalIndexAllowedAppends()

Use this method to specify additional model accessor fields (appends) that can be included in the `index` endpoint response.


## apiDocAdditionalShowAllowedFields()

Use this method to specify additional fields that can be selected for the `show` endpoint. By default, this returns the same fields as `apiDocAdditionalIndexAllowedFields()`.


## apiDocAdditionalShowAllowedAppends()

Use this method to specify additional model accessor fields (appends) that can be included in the `show` endpoint response. By default, this returns the same appends as `apiDocAdditionalIndexAllowedAppends()`.


## apiDocAdditionalAllowedSorts()

Use this method to specify additional fields that can be used for sorting results.


## apiDocAdditionalAllowedIncludes()

Use this method to specify additional relations that can be included in the response.


## apiDocAdditionalAllowedFilters()

Use this method to specify additional filters that can be applied to the query. You can return both simple field names as strings or `AllowedFilter` instances for more complex filters.

## apiFieldsDescription()

Use this method to override the default fields description or append to it.

```php
public static function apiFieldsDescription(): string
{
    return static::apiDefaultFieldsDescription() . '<br>More descriptions.';
}
```

## apiSortsDescription()

Use this method to override the default sorts description or append to it.

```php
public static function apiSortsDescription(): string
{
    return static::apiDefaultSortsDescription() . '<br>More descriptions.';
}
```

## apiAppendsDescription()

Use this method to override the default appends description or append to it.

```php
public static function apiAppendsDescription(): string
{
    return static::apiDefaultAppendsDescription() . '<br>More descriptions.';
}
```

## apiIncludesDescription()

Use this method to override the default includes description or append to it.

```php
public static function apiIncludesDescription(): string
{
    return static::apiDefaultIncludesDescription() . '<br>More descriptions.';
}
```








