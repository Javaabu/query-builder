<?php

namespace Javaabu\QueryBuilder\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Javaabu\QueryBuilder\QueryBuilder;

abstract class ApiController extends ApiBaseController
{

    public function index(Request $request)
    {
        return $this->indexEndpoint($request);
    }

    public function show($model_id, Request $request)
    {
        return $this->showEndpoint($model_id, $request);
    }
}
