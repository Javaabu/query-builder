<?php

namespace Javaabu\QueryBuilder\Exceptions;

use BadMethodCallException;

class AllowedDynamicFieldsMustBeCalledBeforeAllowedFields extends BadMethodCallException
{
    public function __construct()
    {
        parent::__construct("The QueryBuilder's `allowedDynamicFields` method must be called before the `allowedFields` method.");
    }
}
