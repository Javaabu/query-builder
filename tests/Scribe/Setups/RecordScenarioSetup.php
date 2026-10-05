<?php

declare(strict_types=1);

namespace Javaabu\QueryBuilder\Tests\Scribe\Setups;

use Illuminate\Http\Request;
use Javaabu\QueryBuilder\Scribe\Attributes\ResponseScenario;
use Javaabu\QueryBuilder\Scribe\Contracts\ResponseScenarioSetup;
use Knuckles\Camel\Extraction\ExtractedEndpointData;

class RecordScenarioSetup implements ResponseScenarioSetup
{
    public function __invoke(Request $request, ExtractedEndpointData $endpoint_data, ResponseScenario $scenario): void
    {
        $request->attributes->set('setups', [
            ...$request->attributes->get('setups', []),
            $scenario->setup_data['marker'],
        ]);
        $request->headers->set('Authorization', 'Bearer setup-token');
    }
}
