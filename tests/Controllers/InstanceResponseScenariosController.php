<?php

declare(strict_types=1);

namespace Javaabu\QueryBuilder\Tests\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Javaabu\QueryBuilder\Scribe\Attributes\ResponseScenario;

class InstanceResponseScenariosController extends Controller
{
    public function __construct(public ResponseScenario $scenario) {}

    /** @return array<string, ResponseScenario> */
    public function apiDocScenarios(): array
    {
        return ['show' => $this->scenario];
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json(['body' => $request->request->all()]);
    }
}
