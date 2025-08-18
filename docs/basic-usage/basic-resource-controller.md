---
title: Basic resource controller
sidebar_position: 1
---

To create a basic API resource controller using this package, create a controller that extends the `Javaabu\QueryBuilder\Http\Controllers\ApiController` class.

```php
<?php

namespace App\Http\Controllers\Api;

use Javaabu\QueryBuilder\Http\Controllers\ApiController;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;

class ProductsController extends ApiController
{
    /**
     * Get the base query
     *
     * @return Builder
     */
    public function getBaseQuery(): Builder
    {
        return Product::query();
    }

    /**
     * Get the allowed fields
     *
     * @return array
     */
    public function getAllowedFields(): array
    {
        return array_diff(\Schema::getColumnListing('products'), (new Product)->getHidden());
    }

    /**
     * Get the allowed includes
     *
     * @return array
     */
    public function getAllowedIncludes(): array
    {
        return [
            'category'
        ];
    }

    /**
     * Get the allowed appends
     *
     * @return array
     */
    public function getAllowedAppends(): array
    {
        return [
            'formatted_name' => [
                'name',
                'price'
            ],
            
            'formatted_price'
        ];
    }

    /**
     * Get the allowed sorts
     *
     * @return array
     */
    public function getAllowedSorts(): array
    {
        return [
            'id',
            'created_at',
            'updated_at',
            'slug',
            'name',
        ];
    }

    /**
     * Get the default sort
     *
     * @return string
     */
    public function getDefaultSort(): string
    {
        return '-created_at';
    }

    /**
     * Get the allowed filters
     *
     * @return array
     */
    public function getAllowedFilters(): array
    {
        return [
            'id',
            'slug',
            'name',            
            AllowedFilter::scope('search'),
        ];
    }
}
```

The controller will have an `index` and `show` method which you can use to define your routes in your `api.php` route file.

```php
// in api.php route file

/**
 * Products
 */
Route::get('products', [\App\Controllers\Api\ProductsController::class, 'index'])->name('products.index');
Route::get('products/{id}', [\App\Controllers\Api\ProductsController::class, 'show'])->name('products.show');
```

The following methods will have to be implemented in your controller:

## getBaseQuery()
 
This method used to define your base query. It should return a `Illuminate\Database\Eloquent\Builder` instance.
This builder instance will be passed to the `Javaabu\QueryBuilder\QueryBuilder\QueryBuilder::for()` method. 

```php
public function getBaseQuery(): Builder
{
    return Product::query();
}
```

## getAllowedFields()

Used to define which fields that users will be allowed to request through the `?fields=` query parameter. 
If the user doesn't include the `fields` parameter, then all fields defined here will be included by default.

```php
public function getAllowedFields(): array
{
    return array_diff(\Schema::getColumnListing('products'), (new Product)->getHidden());
}
```

## getAllowedIncludes()

Used to define which relations that users will be allowed to request through the `?include=` query parameter.
If the user doesn't include the `include` parameter, then all relations defined here will be included by default.
To not include any relation in a request, the user should submit a blank `?include=` parameter.

```php
public function getAllowedIncludes(): array
{
    return [
        'category'
    ];
}
```

## getAllowedAppends()

Used to define which accessor attibutes that users will be allowed to request through the `?append=` query parameter.
If the user doesn't include the `append` parameter, then all appends defined here will be included by default.
To not include any append in a request, the user should submit a blank `?append=` parameter.

Note that append fields can also be requested through the `fields` query parameter as well.

```php
public function getAllowedAppends(): array
{
    return [             
        'formatted_price'
    ];
}
```

If an append depends on other database columns, then you can specify those fields as an array.
For example, `formatted_name` of the product might be `':name (:price)`. In this case, both `name` and `price` columns are required to generate the `formatted_name`.
If you do not include these columns, the user will get a blank value for `formatted_name` if they don't specifically include `name` and `price` in the fields, when doing a request like `?fields=id,formatted_name`.

```php
public function getAllowedAppends(): array
{
    return [             
        'formatted_name' => [
            'name',
            'price'
        ],
    ];
}
```

## getAllowedSorts()

Defines which fields that the user can sort the records by using the `?sort=` query param. Applies only to the `index` method.
To sort in descending order, users can append a `-` to the field name, e.g. `?sort=-created_at` in the query parameter.
To sort by multiple fields, users can provide a comma-separated list, e.g. `?sort=id,-created_at`

```php
public function getAllowedSorts(): array
{
    return [
        'id',
        'created_at',
        'updated_at',
        'slug',
        'name',
    ];
}
```

## getDefaultSort()

Defines the default sort to apply if the user doesn't provide any `sort` parameter.

```php
public function getDefaultSort(): string
{
    return '-created_at';
}
```

Return an empty string if you don't want any default sort applied.

```php
public function getDefaultSort(): string
{
    return '';
}
```

## getAllowedFilters()

Defines the filters that users can apply using the `?filter[]` query parameter.

```php
public function getAllowedFilters(): array
{
    return [
        'id',
        'slug',
        'name',            
        AllowedFilter::scope('search'),
    ];
}
```
