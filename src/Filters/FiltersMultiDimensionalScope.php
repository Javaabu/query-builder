<?php

namespace Javaabu\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Exceptions\InvalidFilterValue;
use Spatie\QueryBuilder\Filters\FiltersScope;

class FiltersMultiDimensionalScope extends FiltersScope
{
    /**
     * @throws InvalidFilterValue
     */
    public function __invoke(Builder $query, $values, string $property): Builder
    {
        $propertyParts = collect(explode('.', $property));

        $scope = Str::camel($propertyParts->pop());

        $values = Arr::wrap($values);
        $values = $this->resolveParameters($query, $values, $scope);

        $relation = $propertyParts->implode('.');

        if ($relation) {
            return $query->whereHas($relation, function (Builder $query) use (
                $scope,
                $values
            ) {
                return $query->$scope($values);
            });
        }

        return $query->$scope($values);
    }
}
