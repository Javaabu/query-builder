<?php

declare(strict_types=1);

namespace Javaabu\QueryBuilder\Scribe\Contracts;

use Illuminate\Http\Request;
use Javaabu\QueryBuilder\Scribe\Attributes\ResponseScenario;
use Knuckles\Camel\Extraction\ExtractedEndpointData;

interface ResponseScenarioSetup
{
    public function __invoke(
        Request $request,
        ExtractedEndpointData $endpoint_data,
        ResponseScenario $scenario,
    ): void;
}
