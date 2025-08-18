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

    protected function modifyQuery(\Spatie\QueryBuilder\QueryBuilder $query): \Spatie\QueryBuilder\QueryBuilder
    {
        if (request()->has('rating')) {
            $fields = $this->fields()->isNotEmpty() ? $this->fields()->get('_') : [];

            if ($this->sorts()->contains('rating') || $this->sorts()->contains('-rating') || (is_array($fields) && in_array('rating', $fields))) {
                $query->withRating(request()->input('rating'));
            }
        }

        return $query;
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

    public function getAllowedDynamicFields(): array
    {
        if (request()->has('rating')) {
            return [
                'rating'
            ];
        }

        return [];
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
        $sorts = [
            'id',
            'name',
            'slug',
            'created_at',
            'updated_at',
        ];

        if (request()->has('rating')) {
            $sorts[] = 'rating';
        }

        return $sorts;
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
