<?php

namespace Javaabu\QueryBuilder;

use App\Helpers\QueryBuilder\Filters\FiltersMultiDimensionalScope;
use Spatie\QueryBuilder\AllowedFilter;

class AllowedMultiDimensionalFilter extends AllowedFilter
{
    public static function multiScope(string $name, $internalName = null, string $arrayValueDelimiter = null): self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FiltersMultiDimensionalScope(), $internalName);
    }
}
