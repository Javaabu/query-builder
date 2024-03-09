<?php

namespace Javaabu\QueryBuilder\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Javaabu\QueryBuilder\QueryBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\QueryBuilderRequest;
use Illuminate\Routing\Controller as BaseController;

abstract class ApiController extends BaseController
{
    use AuthorizesRequests;
    use ValidatesRequests;
    use DispatchesJobs;

    /**
     * Query builder request
     *
     * @var QueryBuilderRequest
     */
    protected $query_request;

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
     * Unlimited per page
     *
     * @return bool
     */
    protected function allowUnlimitedResultsPerPage(): bool
    {
        return property_exists($this, 'allow_unlimited_results') ? $this->allow_unlimited_results : false;
    }

    /**
     * Check if the request wants all the results
     *
     * @param Request $request
     * @return bool
     */
    protected function wantsAllResults(Request $request): bool
    {
        return $request->input('per_page') == -1;
    }

    /**
     * Get per page
     *
     * @param Request $request
     * @param int $default
     * @return int
     */
    protected function getPerPage(Request $request, int $default = 0)
    {
        return abs($request->input('per_page', $default));
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return QueryBuilder[]|LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection|\Spatie\QueryBuilder\QueryBuilder[]
     * @throws ValidationException
     */
    public function index(Request $request)
    {

        $this->validate($request, $this->getIndexValidation());

        $query = QueryBuilder::for($this->getBaseQuery());

        if ($default_sort = $this->getDefaultSort()) {
            $query->defaultSort($default_sort);
        }

        $query->allowedSorts($this->getAllowedSorts())
            ->allowedFilters($this->getAllowedFilters())
            ->allowedAppends($this->getAllowedAppends())
            ->fieldsToAlwaysInclude($this->getFieldsToAlwaysInclude())
            ->allowedFields($this->getAllowedFields())
            ->allowedIncludes($this->getAllowedIncludes());

        $query = $this->modifyQuery($query);

        if ($this->allowUnlimitedResultsPerPage() && $this->wantsAllResults($request)) {
            return $query->get();
        }

        return $query->paginate($this->getPerPage($request, 10))
                       ->appends($request->except(['page']));
    }

    /**
     * Display a single resource.
     *
     * @param $model_id
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Collection|Model|QueryBuilder|QueryBuilder[]|null
     * @throws ValidationException
     */
    public function show($model_id, Request $request)
    {
        $this->validate($request, $this->getShowValidation());

        try {
            $model = QueryBuilder::for($this->getBaseQuery())
                ->allowedAppends($this->getAllShowAllowedAppends())
                ->fieldsToAlwaysInclude($this->getFieldsToAlwaysInclude())
                ->allowedFields($this->getAllowedFields())
                ->allowedIncludes($this->getAllowedIncludes());

            $model = $this->modifyQuery($model)->findOrFail($model_id);

            $this->authorizeView($model);

            return $this->modifyModel($model);
        } catch (ModelNotFoundException $e) {
            abort(404, 'Not Found');
        }
    }

    /**
     * Modify the model
     */
    public function modifyModel(Model $model): Model
    {
        return $model;
    }

    /**
     * Check if allowed to view
     *
     * @param Model $model
     */
    protected function authorizeView(Model $model): void
    {
        return;
    }

    /**
     * Get the index validation
     */
    protected function getIndexValidation(): array
    {
        return [
            'per_page' => "integer|" . ($this->allowUnlimitedResultsPerPage() ? 'min:-1' : 'between:1,50'),
            'page' => 'integer|min:1',
        ];
    }

    /**
     * Get the fields to always include
     */
    protected function getFieldsToAlwaysInclude(): array
    {
        return [
            'id'
        ];
    }

    /**
     * Get the index allowed fields
     */
    protected function getIndexAllowedFields(): array
    {
        return $this->getAllowedFields();
    }

    /**
     * Get the show validation
     */
    protected function getShowValidation(): array
    {
        return [];
    }

    /**
     * Get the query request
     *
     * @return QueryBuilderRequest
     */
    protected function getQueryRequest(): QueryBuilderRequest
    {
        return $this->query_request;
    }

    /**
     * Get the query fields
     *
     * @return Collection
     */
    protected function fields(): Collection
    {
        return $this->getQueryRequest()->fields();
    }

    /**
     * Get the query appends
     *
     * @return Collection
     */
    protected function appends(): Collection
    {
        return $this->getQueryRequest()->appends();
    }

    /**
     * Get the query includes
     *
     * @return Collection
     */
    protected function includes(): Collection
    {
        return $this->getQueryRequest()->includes();
    }

    /**
     * Get the query sorts
     *
     * @return Collection
     */
    protected function sorts(): Collection
    {
        return $this->getQueryRequest()->sorts();
    }

    /**
     * Get the query filters
     *
     * @return Collection
     */
    protected function filters(): Collection
    {
        return $this->getQueryRequest()->filters();
    }

    /**
     * Modify the query after adding query builder params
     *
     * @param \Spatie\QueryBuilder\QueryBuilder $query
     * @return QueryBuilder
     */
    protected function modifyQuery(\Spatie\QueryBuilder\QueryBuilder $query): \Spatie\QueryBuilder\QueryBuilder
    {
        return $query;
    }

    /**
     * Get the allowed append attributes
     *
     * @return array
     */
    protected function getAllowedAppendAttributes(): array
    {
        return Arr::rootKeys($this->getAllowedAppends());
    }

    /**
     * Get the allowed append attributes
     *
     * @return array
     */
    protected function getShowAllowedAppendAttributes(): array
    {
        return Arr::rootKeys($this->getAllShowAllowedAppends());
    }

    /**
     * Get the show allowed appends
     */
    protected function getAllShowAllowedAppends(): array
    {
        if (! $this->getShowAllowedAppends()) {
            return $this->getAllowedAppends();
        }

        return array_merge(
            $this->getAllowedAppends(),
            $this->getShowAllowedAppends()
        );
    }


    /**
     * Get the allowed appends for only show endpoint
     */
    protected function getShowAllowedAppends(): array
    {
        return [];
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
