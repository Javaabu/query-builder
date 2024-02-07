<?php

namespace Javaabu\QueryBuilder\Exceptions;

use BadMethodCallException;

class AllowedAppendsMustBeCalledBeforeAllowedFields extends BadMethodCallException
{
    public function __construct()
    {
        parent::__construct("The QueryBuilder's `allowedAppends` method must be called before the `allowedFields` method.");
    }
}
