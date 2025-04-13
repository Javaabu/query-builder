<?php

namespace Javaabu\QueryBuilder\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\ValidationException;
use Javaabu\QueryBuilder\Concerns\IsApiController;
use Javaabu\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\QueryBuilderRequest;

abstract class ApiBaseController extends BaseController
{
    use IsApiController;

    /**
     * Constructor
     *
     * @param QueryBuilderRequest $request
     */
    public function __construct(QueryBuilderRequest $request)
    {
        $this->query_request = $request;
    }

    /**
     * Get the base query
     */
    protected abstract function getBaseQuery(): Builder;

    /**
     * Get the fields
     */
    protected abstract function getAllowedFields(): array;

    /**
     * Get the includes
     */
    protected abstract function getAllowedIncludes(): array;

    /**
     * Get the allowed appends
     */
    protected abstract function getAllowedAppends(): array;

    /**
     * Get the allowed sorts
     */
    protected abstract function getAllowedSorts(): array;

    /**
     * Get the default sort
     */
    protected abstract function getDefaultSort(): string;

    /**
     * Get the allowed filters
     */
    protected abstract function getAllowedFilters(): array;
}
