<?php

namespace Javaabu\QueryBuilder\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Javaabu\QueryBuilder\QueryBuilder;

abstract class ApiController extends ApiBaseController
{

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return QueryBuilder[]|LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection|\Spatie\QueryBuilder\QueryBuilder[]
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        return $this->indexEndpoint($request);
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
        return $this->showEndpoint($model_id, $request);
    }
}
