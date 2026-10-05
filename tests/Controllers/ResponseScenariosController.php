<?php

declare(strict_types=1);

namespace Javaabu\QueryBuilder\Tests\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Javaabu\QueryBuilder\Scribe\Attributes\ResponseScenario;
use Javaabu\QueryBuilder\Tests\Models\Product;

class ResponseScenariosController extends Controller
{
    public static mixed $definitions = [];

    public static function apiDocScenarios(): mixed
    {
        return static::$definitions;
    }

    public function show(Request $request, ?string $id = null): JsonResponse
    {
        return response()->json([
            'id' => $id,
            'body' => $request->request->all(),
            'query' => $request->query->all(),
            'cookie' => $request->cookie('example'),
            'authorization' => $request->header('Authorization'),
            'server_authorization' => $request->server('HTTP_AUTHORIZATION'),
            'redirect_authorization' => $request->server('REDIRECT_HTTP_AUTHORIZATION'),
            'config' => config('app.name'),
            'file' => $request->file('attachment')?->getClientOriginalName(),
            'setups' => $request->attributes->get('setups', []),
            'user' => $request->user()?->id,
        ]);
    }

    #[ResponseScenario(name: 'Attribute response', body: ['source' => 'attribute'], expected_status: 200)]
    #[ResponseScenario(name: 'Second attribute', body: ['source' => 'second attribute'], expected_status: 200)]
    public function attribute(Request $request): JsonResponse
    {
        return $this->show($request);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string']]);
        $product = Product::factory()->create($data);

        return response()->json(['name' => $product->name], 201);
    }

    public function products(): JsonResponse
    {
        return response()->json(['names' => Product::query()->pluck('name')->all()]);
    }
}
