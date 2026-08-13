<?php

namespace Javaabu\QueryBuilder\Tests\Controllers;

use Javaabu\QueryBuilder\Http\Controllers\ApiController;
use Javaabu\QueryBuilder\Tests\Models\Brand;
use Illuminate\Database\Eloquent\Builder;

class BrandsController extends ApiController
{
    /**
     * Get the base query
     *
     * @return Builder
     */
    public function getBaseQuery(): Builder
    {
        return Brand::query();
    }

    public function getRouteKeyName(): ?string
    {
        return 'slug';
    }

    /**
     * Get the allowed fields
     *
     * @return array
     */
    public function getAllowedFields(): array
    {
        return array_diff(\Schema::getColumnListing('brands'), (new Brand)->getHidden());
    }

    /**
     * Get the allowed includes
     *
     * @return array
     */
    public function getAllowedIncludes(): array
    {
        return [
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
        ];
    }
}
