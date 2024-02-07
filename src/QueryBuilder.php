<?php
/**
 * Custom query builder
 */

namespace Javaabu\QueryBuilder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Javaabu\QueryBuilder\Concerns\AppendsAttributesToResults;
use Javaabu\QueryBuilder\Exceptions\AllowedAppendsMustBeCalledBeforeAllowedFields;
use Javaabu\QueryBuilder\Exceptions\FieldsToAlwaysIncludeMustBeCalledBeforeAllowedFields;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\Exceptions\InvalidFieldQuery;

class QueryBuilder extends \Spatie\QueryBuilder\QueryBuilder
{
    use AppendsAttributesToResults;

    /**
     * Weather to validate filters
     *
     * @var bool
     */
    protected $ignoreInvalidFilters = false;

    /**
     * Fields to include with appends
     *
     * @var Collection
     */
    protected $fieldsToInclude;

    /**
     * Fields to always include
     *
     * @var Collection
     */
    protected $fieldsToAlwaysInclude;

    /**
     * All appends
     *
     * @var array
     */
    protected $allAppends = null;

    /**
     * Set to ignore invalid filters
     */
    public function ignoreInvalidFilters(): self
    {
        $this->ignoreInvalidFilters = true;

        return $this;
    }

    /**
     * Validate filters conditionally
     */
    protected function ensureAllFiltersExist()
    {
        if (!$this->ignoreInvalidFilters) {
            parent::ensureAllFiltersExist();
        }
    }

    /**
     * By default include all includes
     *
     * @param  Collection  $includes
     */
    protected function addIncludesToQuery(Collection $includes)
    {
        if (!$this->request->has('include')) {
            $includes = $this->allowedIncludes->map(function (AllowedInclude $allowedInclude) {
                return $allowedInclude->getName();
            });
        }

        parent::addIncludesToQuery($includes);
    }

    /**
     * Need to know allowed appends to allow appends via the fields param
     * Also include fields during appends
     *
     * @param $appends
     * @return $this
     */
    public function allowedAppends($appends): self
    {
        if ($this->allowedFields instanceof Collection) {
            throw new AllowedAppendsMustBeCalledBeforeAllowedFields();
        }

        $appends = is_array($appends) ? $appends : func_get_args();

        $fieldsToInclude = [];
        $allowedAppends = [];

        foreach ($appends as $key => $value) {
            if (is_array($value)) {
                $fieldsToInclude[$key] = $value;
                $allowedAppends[] = $key;
            } else {
                $allowedAppends[] = $value;
            }
        }

        $this->allowedAppends = collect($allowedAppends);
        $this->fieldsToInclude = collect($fieldsToInclude);

        $this->ensureAllAppendsExist();

        return $this;
    }

    /**
     * Set which fields to always include
     *
     * @param $fields
     * @return $this
     */
    public function fieldsToAlwaysInclude($fields): self
    {
        if ($this->allowedFields instanceof Collection) {
            throw new FieldsToAlwaysIncludeMustBeCalledBeforeAllowedFields();
        }

        $fields = is_array($fields) ? $fields : func_get_args();

        $this->fieldsToAlwaysInclude = collect($fields);

        return $this;
    }

    /**
     * Modified to support fields without table name
     */
    protected function ensureAllFieldsExist()
    {
        $subjectTable = $this->getSubject()->getModel()->getTable();

        $requestedFields = $this->request->fields()
            ->map(function ($fields, $model) use ($subjectTable) {
                if ($model) {
                    $tableName = Str::snake(preg_replace('/-/', '_', $model));
                } else {
                    $tableName = $subjectTable;
                }

                $fields = array_map([Str::class, 'snake'], $fields);

                return $this->prependFieldsWithTableName($fields, $tableName);
            })
            ->flatten()
            ->unique();

        // get rid of any appended fields present
        $requestedFields = $requestedFields->diff(
            $this->prependFieldsWithTableName(($this->allowedAppends ? $this->allowedAppends->all() : []), $subjectTable)
        );

        $unknownFields = $requestedFields->diff($this->allowedFields);

        //dd($unknownFields);

        if ($unknownFields->isNotEmpty()) {
            throw InvalidFieldQuery::fieldsNotAllowed($unknownFields, $this->allowedFields);
        }
    }

    /**
     * Get field appends if any present
     *
     * @return array
     */
    protected function getFieldAppends()
    {
        $subjectTable = $this->getSubject()->getModel()->getTable();

        return $this->request->fields()
            ->map(function ($fields, $model) use ($subjectTable) {

                if ($model) {
                    $tableName = Str::snake(preg_replace('/-/', '_', $model));
                } else {
                    $tableName = $subjectTable;
                }

                $fields = array_map([Str::class, 'snake'], $fields);
                if ($tableName == $subjectTable) {
                    return $this->retrieveFieldsToAppends($fields);
                }

                return [];
            })
            ->flatten()
            ->filter()
            ->unique()
            ->all();
    }

    /**
     * Append fields
     *
     * @param  array  $fields
     * @return array
     */
    protected function retrieveFieldsToAppends(array $fields): array
    {
        if (!$this->allowedAppends instanceof Collection) {
            return [];
        }

        return collect($fields)->intersect($this->allowedAppends)->values()->all();
    }

    /**
     * Include both appends and field appends
     *
     * @param  Collection  $results
     * @return Collection
     */
    protected function addAppendsToResults(Collection $results)
    {
        return $results->each(function (Model $result) {
            $to_append = $this->getAllAppends();

            return $result->append($to_append);
        });
    }

    /**
     * Get all the appends
     *
     * @return array
     */
    protected function getAllAppends(): array
    {
        if (!is_array($this->allAppends)) {
            // append all by default
            $request_appends = $this->request->has('append') ? $this->request->appends()->toArray()
                : ($this->allowedAppends ? $this->allowedAppends->all() : []);

            $fields = array_merge($request_appends, $this->getFieldAppends());
            $this->allAppends = collect($fields)->intersect($this->allowedAppends)->values()->all();
        }

        return $this->allAppends;
    }

    /**
     * Modified to support fields without table name
     */
    protected function addRequestedModelFieldsToQuery()
    {
        $modelTableName = $this->getSubject()->getModel()->getTable();
        $prepend_table_name = true;

        if ($this->request->has('fields')) {
            $modelFields = $this->request->fields()->get($modelTableName);

            if (!$modelFields) {
                $modelFields = $this->request->fields()->get(0);
            }
        } else {
            //$prepend_table_name = false;
            $modelFields = $this->allowedFields->all();
        }

        // get all append fields to include
        $appends = $this->getAllAppends();

        if ($this->fieldsToInclude instanceof Collection) {
            $fieldsToInclude = $this->fieldsToInclude
                ->only($appends)
                ->flatten()
                ->unique()
                ->all();
        } else {
            $fieldsToInclude = [];
        }


        $modelFields = array_merge($modelFields ?: [], $fieldsToInclude);

        if ($this->fieldsToAlwaysInclude instanceof Collection) {
            $modelFields = array_unique(array_merge($modelFields, $this->fieldsToAlwaysInclude->all()));
        }

        if (empty($modelFields)) {
            return;
        }

        $prependedFields = $prepend_table_name ? $this->prependFieldsWithTableName($modelFields, $modelTableName) : $modelFields;

        // get rid of any appended fields present
        $prependedFields = array_diff(
            $prependedFields,
            $this->prependFieldsWithTableName(($this->allowedAppends ? $this->allowedAppends->all() : []), $modelTableName)
        );

        $prependedFields = array_unique($prependedFields);

        $this->select($prependedFields);
    }

    public function __call($name, $arguments)
    {
        $result = $this->forwardCallTo($this->subject, $name, $arguments);

        /*
         * If the forwarded method call is part of a chain we can return $this
         * instead of the actual $result to keep the chain going.
         */
        if ($result === $this->subject) {
            return $this;
        }

        if ($result instanceof Model) {
            $this->addAppendsToResults(collect([$result]));
        }

        if ($result instanceof Collection) {
            $this->addAppendsToResults($result);
        }

        if ($result instanceof LengthAwarePaginator) {
            $this->addAppendsToResults(collect($result->items()));
        }

        return $result;
    }
}
