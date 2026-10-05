<?php

declare(strict_types=1);

namespace Javaabu\QueryBuilder\Scribe;

use Illuminate\Support\Str;
use Knuckles\Camel\Extraction\Response;
use Knuckles\Camel\Output\OutputEndpointData;
use Knuckles\Scribe\Writing\OpenApiSpecGenerators\OpenApiGenerator;

final class ResponseExamplesOpenApiGenerator extends OpenApiGenerator
{
    /**
     * Convert responses sharing the same status code into named OpenAPI
     * examples so external UIs such as Scalar can display every scenario.
     *
     * @param array<int, array{
     *     description: string,
     *     name: string,
     *     endpoints: OutputEndpointData[]
     * }> $grouped_endpoints
     */
    public function pathItem(
        array $path_item,
        array $grouped_endpoints,
        OutputEndpointData $endpoint,
    ): array {
        $responses_by_status = $endpoint->responses->groupBy(
            static fn (Response $response): string => (string) $response->status,
        );

        foreach ($responses_by_status as $status => $responses) {
            /*
             * A named examples collection is only necessary when more than one
             * scenario shares the same response status.
             */
            if ($responses->count() < 2) {
                continue;
            }

            if (! isset($path_item['responses'][$status]['content'])) {
                continue;
            }

            foreach ($responses->values() as $index => $response) {
                /*
                 * Binary responses cannot be represented as inline OpenAPI
                 * examples.
                 */
                if (
                    $response->content !== null
                    && str_starts_with($response->content, '<<binary>>')
                ) {
                    continue;
                }

                $content_type = $this->resolveContentType(
                    $path_item['responses'][$status]['content'],
                    $response,
                );

                if ($content_type === null) {
                    continue;
                }

                $summary = trim((string) $response->description);

                if ($summary === '') {
                    $summary = sprintf('Scenario %d', $index + 1);
                }

                $examples = &$path_item['responses'][$status]['content'][$content_type]['examples'];

                $key = $this->uniqueExampleKey(
                    $summary,
                    $index + 1,
                    $examples ?? [],
                );

                $examples[$key] = [
                    'summary' => $summary,
                    'value' => $this->decodeContent($response->content),
                ];

                unset($examples);
            }
        }

        return $path_item;
    }

    /**
     * Find the media type that Scribe generated for this response.
     *
     * @param  array<string, mixed>  $content
     */
    private function resolveContentType(
        array $content,
        Response $response,
    ): ?string {
        $headers = array_change_key_case(
            $response->headers,
            CASE_LOWER,
        );

        $declared_content_type = $headers['content-type']
            ?? 'application/json';

        if (array_key_exists($declared_content_type, $content)) {
            return $declared_content_type;
        }

        /*
         * Fall back to Scribe's generated media type. This handles values such
         * as "application/json; charset=UTF-8".
         */
        return array_key_first($content);
    }

    /**
     * Generate a unique OpenAPI example key from the scenario description.
     *
     * @param  array<string, mixed>  $existing_examples
     */
    private function uniqueExampleKey(
        string $summary,
        int $position,
        array $existing_examples,
    ): string {
        $base_key = Str::snake(Str::ascii($summary));

        if ($base_key === '') {
            $base_key = sprintf('scenario_%d', $position);
        }

        $key = $base_key;
        $suffix = 2;

        while (array_key_exists($key, $existing_examples)) {
            $key = sprintf('%s_%d', $base_key, $suffix);
            $suffix++;
        }

        return $key;
    }

    /**
     * Decode JSON responses while preserving plain-text response content.
     */
    private function decodeContent(?string $content): mixed
    {
        if ($content === null) {
            return null;
        }

        $decoded = json_decode($content, true);

        return json_last_error() === JSON_ERROR_NONE
            ? $decoded
            : $content;
    }
}
