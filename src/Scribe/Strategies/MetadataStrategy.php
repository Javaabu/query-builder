<?php

namespace Javaabu\QueryBuilder\Scribe\Strategies;

use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\Strategy;
use Knuckles\Scribe\Tools\Utils as u;

class MetadataStrategy extends Strategy
{

    public function __invoke(ExtractedEndpointData $endpointData, array $settings = []): ?array
    {
        [$controller, $method] = u::getRouteClassAndMethodNames($endpointData->route);

        if (method_exists($controller, 'apiDocControllerMethodMetadata') && ($metadata = $controller::apiDocControllerMethodMetadata($method))) {
            return $metadata;
        }

        return null;
    }
}
