---
title: Introduction
sidebar_position: 1.0
---

# Query Builder


[Query Builder](https://github.com/Javaabu/query-builder) Modifications on top of spatie/query-builder.
Provides helper classes for creating API controllers and supports [Scribe](https://github.com/knuckleswtf/scribe/) for automatically generating API docs.

For example, if you have a `Product` model, you can create an API controller like so:

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
            ]
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
