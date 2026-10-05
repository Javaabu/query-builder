<?php

declare(strict_types=1);

namespace Javaabu\QueryBuilder\Tests\Scribe\Setups;

use Illuminate\Http\Request;
use Javaabu\QueryBuilder\Scribe\Attributes\ResponseScenario;
use Javaabu\QueryBuilder\Scribe\Contracts\ResponseScenarioSetup;
use Javaabu\QueryBuilder\Tests\Models\Product;
use Knuckles\Camel\Extraction\ExtractedEndpointData;

class CreateProductSetup implements ResponseScenarioSetup
{
    public function __invoke(Request $request, ExtractedEndpointData $endpoint_data, ResponseScenario $scenario): void
    {
        Product::factory()->create(['name' => $scenario->setup_data['name']]);
    }
}
