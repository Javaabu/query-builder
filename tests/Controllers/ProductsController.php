<?php

namespace Javaabu\QueryBuilder\Tests\Controllers;

use Javaabu\QueryBuilder\Http\Controllers\ApiController;
use Javaabu\QueryBuilder\Tests\Models\Product;
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
            'brand',
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
            'name',
            'slug',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Get the default sort
     *
     * @return string
     */
    public function getDefaultSort(): string
    {
        return 'name';
    }

    /**
     * Get the allowed filters
     *
     * @return array
     */
    public function getAllowedFilters(): array
    {
        return [
            'name',
            AllowedFilter::scope('search'),
        ];
    }
}
