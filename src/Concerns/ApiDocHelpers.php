<?php

namespace Javaabu\QueryBuilder\Concerns;

use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;

trait ApiDocHelpers
{
    public static function apiDocDefaultSort(): string
    {
        /** @var self $new_instance */
        $new_instance = app(static::class);

        return $new_instance->getDefaultSort();
    }

    public static function apiDocAllowedFilters(): array
    {
        /** @var self $new_instance */
        $new_instance = app(static::class);

        return $new_instance->getAllowedFilters();
    }

    public static function apiDocAllowedIncludes(): array
    {
        /** @var self $new_instance */
        $new_instance = app(static::class);

        return $new_instance->getAllowedIncludes();
    }

    public static function apiDocIndexAllowedFields(): array
    {
        /** @var self $new_instance */
        $new_instance = app(static::class);

        return array_merge($new_instance->getIndexAllowedFields(), $new_instance->getAllowedAppendAttributes());
    }

    public static function apiDocIndexAllowedAppends(): array
    {
        /** @var self $new_instance */
        $new_instance = app(static::class);

        return $new_instance->getAllowedAppendAttributes();
    }

    public static function apiDocShowAllowedFields(): array
    {
        /** @var self $new_instance */
        $new_instance = app(static::class);

        return array_merge($new_instance->getAllowedFields(), $new_instance->getShowAllowedAppendAttributes());
    }

    public static function apiDocShowAllowedAppends(): array
    {
        /** @var self $new_instance */
        $new_instance = app(static::class);

        return $new_instance->getShowAllowedAppendAttributes();
    }

    public static function apiDocAllowedSorts(): array
    {
        /** @var self $new_instance */
        $new_instance = app(static::class);

        return $new_instance->getAllowedSorts();
    }

    public static function apiDocDefaultQueryParameters(
        array $fields = [],
        array $sorts = [],
        string $default_sort = '',
        array $appends = [],
        array $includes = [],
        array $filters = [],
        array $filter_metadata = []
    ): array
    {
        $params = [];

        if ($fields) {
            $params['fields'] = [
                'type' => 'string',
                'description' => 'Fields to include in the response. ' .
                    'You can provide the fields as a comma-separated list, or provide the fields as an array query parameter i.e `fields[]`. ' .
                    'By default all fields are included if the `fields` parameter is missing.'
                    . '<br><br> **Allowed values:** ' . "\n" . implode("\n", array_map(fn($field) => "- `$field`", $fields)),
                'enum' => $fields,
                'example' => implode(',', $fields),
            ];
        }

        if ($sorts) {
            $params['sort'] = [
                'type' => 'string',
                'description' => 'Which fields to sort the results by. '.
                    '<br>To sort in descending order, append a `-` to the field name, e.g. `?sort=-created_at`. '.
                    '<br>To sort by multiple fields, provide a comma-separated list, e.g. `?sort=id,-created_at`. '.
                    '<br><br>**Allowed sorts:** ' . "\n" . implode("\n", array_map(fn ($field) => "- `$field`", $sorts)) . "\n\n" .
                    '<br>**Default sort:** ' . ($default_sort ? '`' . $default_sort . '`' : 'None'),
                'enum' => static::apiDocAllowedSorts(),
                'example' => static::apiDocDefaultSort(),
            ];
        }

        if ($appends) {
            $params['append'] = [
                'type' => 'string',
                'description' => 'Model accessor fields to include in the response. ' .
                    'You can provide the append field as a comma-separated list, or provide the fields as an array query parameter i.e `append[]`. ' .
                    'By default all appends are included if the `append` parameter is missing. ' .
                    'To not append any fields, provide an empty string `?append=`'
                    . '<br><br> **Allowed values:** ' . "\n" . implode("\n", array_map(fn($field) => "- `$field`", $appends)),
                'enum' => $appends,
                'example' => implode(',', $appends),
            ];
        }

        if ($includes) {
            $params['include'] = [
                'type' => 'string',
                'description' => 'Model relations to include in the response. ' .
                    'You can provide the includes as a comma-separated list, or provide the includes as an array query parameter i.e `include[]`. ' .
                    'By default all includes are included if the `include` parameter is missing. ' .
                    'To not include any relations, provide an empty string `?include=`'
                    . '<br><br> **Allowed values:** ' . "\n" . implode("\n", array_map(fn($field) => "- `$field`", $includes)),
                'enum' => $includes,
                'example' => implode(',', $includes),
            ];
        }

        $singular_resource_name = static::apiDocResourceNameSingularLower();

        if ($filters) {
            foreach ($filters as $filter) {
                $filter_name = '';

                if (is_string($filter)) {
                    $filter_name = $filter;
                } elseif ($filter instanceof AllowedFilter) {
                    $filter_name = $filter->getName();
                }

                if (! $filter_name) {
                    continue;
                }

                $metadata = $filter_metadata[$filter_name] ?? [];

                $params["filter[{$filter_name}]"] = array_merge([
                    'type' => 'string',
                    'description' => is_string($filter) ? 'Filter by the ' . Str::lower(slug_to_title($filter))  . ' of the ' . $singular_resource_name : 'Apply the ' . Str::lower(slug_to_title($filter_name)) . ' filter',
                ], $metadata);
            }
        }

        return $params;
    }

    public static function apiDocDefaultIndexQueryParameters(): array
    {
        return static::apiDocDefaultQueryParameters(
            static::apiDocIndexAllowedFields(),
            static::apiDocAllowedSorts(),
            static::apiDocDefaultSort(),
            static::apiDocIndexAllowedAppends(),
            static::apiDocAllowedIncludes(),
            static::apiDocAllowedFilters(),
            static::apiDocFilterMetadata()
        );
    }

    public static function apiDocDefaultShowQueryParameters(): array
    {
        return static::apiDocDefaultQueryParameters(
            fields: static::apiDocShowAllowedFields(),
            appends: static::apiDocShowAllowedAppends(),
            includes: static::apiDocAllowedIncludes()
        );
    }

    public static function apiDocResourceName(): string
    {
        $class_name = Str::of(class_basename(static::class))
            ->camel()
            ->snake()
            ->replace('_', ' ')
            ->title()
            ->toString();

        if (Str::endsWith($class_name, 'Controller')) {
            $class_name = trim(Str::beforeLast($class_name, 'Controller'));
        }

        return $class_name;
    }

    public static function apiDocResourceNameSingular(): string
    {
        return Str::singular(static::apiDocResourceName());
    }

    public static function apiDocResourceNameSingularLower(): string
    {
        return Str::lower(static::apiDocResourceNameSingular());
    }

    public static function apiDocResourceNameLower(): string
    {
        return Str::lower(static::apiDocResourceName());
    }

    public static function apiDocGroupMetadata(): array
    {
        return [
            'groupName' => static::apiDocGroupName(),
            'groupDescription' => static::apiDocGroupDescription(),
        ];
    }

    public static function apiDocGroupName(): string
    {
        return static::apiDocResourceName();
    }

    public static function apiDocGroupDescription(): string
    {
        return 'Endpoints for listing and viewing ' . static::apiDocResourceNameLower();
    }

    public static function apiDocIndexTitle(): string
    {
        return 'List all ' . static::apiDocResourceNameLower();
    }

    public static function apiDocIndexDescription(): string
    {
        return 'Fetch all ' . static::apiDocResourceNameLower() . '. Supports filtering, sorting, pagination and field selection.';
    }

    public static function apiDocShowTitle(): string
    {
        return 'View a single ' . static::apiDocResourceNameSingularLower();
    }

    public static function apiDocShowDescription(): string
    {
        return 'Fetch a single ' . static::apiDocResourceNameSingularLower() . '. Supports field selection.';
    }

    public static function apiDocFilterMetadata(): array
    {
        return [
            'id' => [
                'type' => 'integer',
            ],

            'search' => [
                'description' => 'Search ' . static::apiDocResourceNameLower() . '.',
            ]
        ];
    }

    public static function apiDocIndexMetadata(): array
    {
        return [
            ...static::apiDocGroupMetadata(),
            'title' => static::apiDocIndexTitle(),
            'description' => static::apiDocIndexDescription(),
        ];
    }

    public static function apiDocShowMetadata(): array
    {
        return [
            ...static::apiDocGroupMetadata(),
            'title' => static::apiDocShowTitle(),
            'description' => static::apiDocShowDescription(),
        ];
    }

    public static function apiDocIndexQueryParameters(): array
    {
        return static::apiDocDefaultIndexQueryParameters();
    }

    public static function apiDocShowQueryParameters(): array
    {
        return static::apiDocDefaultShowQueryParameters();
    }

    public static function apiDocControllerMethodMetadata(string $method): array
    {
        return match ($method) {
            'index' => static::apiDocIndexMetadata(),
            'show' => static::apiDocShowMetadata(),
            default => static::apiDocGroupMetadata(),
        };
    }

    public static function apiDocControllerMethodQueryParameters(string $method): array
    {
        return match ($method) {
            'index' => static::apiDocIndexQueryParameters(),
            'show' => static::apiDocShowQueryParameters(),
            default => [],
        };
    }
}
