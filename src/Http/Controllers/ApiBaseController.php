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
    public abstract function getBaseQuery(): Builder;

    /**
     * Get the fields
     */
    public abstract function getAllowedFields(): array;

    /**
     * Get the includes
     */
    public abstract function getAllowedIncludes(): array;

    /**
     * Get the allowed appends
     */
    public abstract function getAllowedAppends(): array;

    /**
     * Get the allowed sorts
     */
    public abstract function getAllowedSorts(): array;

    /**
     * Get the default sort
     */
    public abstract function getDefaultSort(): string;

    /**
     * Get the allowed filters
     */
    public abstract function getAllowedFilters(): array;
}
