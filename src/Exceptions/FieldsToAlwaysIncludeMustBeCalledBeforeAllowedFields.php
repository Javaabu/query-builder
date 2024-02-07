<?php

namespace Javaabu\QueryBuilder\Exceptions;

use BadMethodCallException;

class FieldsToAlwaysIncludeMustBeCalledBeforeAllowedFields extends BadMethodCallException
{
    public function __construct()
    {
        parent::__construct("The QueryBuilder's `fieldsToAlwaysAppend` method must be called before the `allowedFields` method.");
    }
}
