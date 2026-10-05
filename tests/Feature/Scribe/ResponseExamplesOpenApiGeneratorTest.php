<?php

declare(strict_types=1);

namespace Javaabu\QueryBuilder\Tests\Feature\Scribe;

use Javaabu\QueryBuilder\Scribe\ResponseExamplesOpenApiGenerator;
use Javaabu\QueryBuilder\Tests\TestCase;
use Knuckles\Camel\Extraction\Metadata;
use Knuckles\Camel\Output\OutputEndpointData;
use Knuckles\Scribe\Tools\DocumentationConfig;
use PHPUnit\Framework\Attributes\Test;

class ResponseExamplesOpenApiGeneratorTest extends TestCase
{
    #[Test]
    public function it_preserves_schemas_and_adds_unique_examples_for_same_status_responses(): void
    {
        $endpoint = $this->endpoint('api/v1/profiles', [
            ['status' => 200, 'description' => 'Reader found', 'content' => '{"name":"Aisha"}', 'headers' => ['Content-Type' => 'application/json']],
            ['status' => 200, 'description' => 'Reader found', 'content' => '{"name":"Ali"}', 'headers' => ['content-type' => 'application/json; charset=UTF-8']],
            ['status' => 200, 'description' => '', 'content' => 'plain text'],
            ['status' => 200, 'description' => 'No content', 'content' => null],
            ['status' => 200, 'description' => 'File', 'content' => '<<binary>> image'],
            ['status' => 404, 'description' => 'Missing', 'content' => '{}'],
        ]);
        $path = ['responses' => [200 => ['content' => ['application/json' => ['schema' => ['type' => 'object'], 'examples' => ['reader_found' => ['value' => 'existing']]]]], 404 => ['description' => 'Missing']]];

        $result = (new ResponseExamplesOpenApiGenerator(new DocumentationConfig))->pathItem($path, [], $endpoint);

        $content = $result['responses'][200]['content']['application/json'];
        $this->assertSame(['type' => 'object'], $content['schema']);
        $this->assertSame([
            'reader_found' => ['value' => 'existing'],
            'reader_found_2' => ['summary' => 'Reader found', 'value' => ['name' => 'Aisha']],
            'reader_found_3' => ['summary' => 'Reader found', 'value' => ['name' => 'Ali']],
            'scenario3' => ['summary' => 'Scenario 3', 'value' => 'plain text'],
            'no_content' => ['summary' => 'No content', 'value' => null],
        ], $content['examples']);
        $this->assertSame($path['responses'][404], $result['responses'][404]);
    }

    #[Test]
    public function it_skips_responses_without_a_generated_content_type(): void
    {
        $endpoint = $this->endpoint('api/v1/profiles', [
            ['status' => 200, 'description' => 'One', 'content' => '{}'],
            ['status' => 200, 'description' => 'Two', 'content' => '{}'],
            ['status' => 204, 'description' => 'Empty one', 'content' => null],
            ['status' => 204, 'description' => 'Empty two', 'content' => null],
        ]);
        $path = ['responses' => [200 => ['content' => []], 204 => ['description' => 'Empty']]];

        $result = (new ResponseExamplesOpenApiGenerator(new DocumentationConfig))->pathItem($path, [], $endpoint);

        $this->assertSame($path, $result);
    }

    /** @param list<array<string, mixed>> $responses */
    private function endpoint(string $uri, array $responses = []): OutputEndpointData
    {
        return new OutputEndpointData([
            'uri' => $uri,
            'httpMethods' => ['POST'],
            'metadata' => new Metadata,
            'responses' => $responses,
        ]);
    }
}
