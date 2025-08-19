<?php

namespace Javaabu\QueryBuilder\Concerns;

use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;

trait ApiDocHelpers
{
    public static function apiDocAdditionalIndexQueryParameters(): array
    {
        return [];
    }

    public static function apiDocAdditionalShowQueryParameters(): array
    {
        return [];
    }

    public static function apiDocAdditionalIndexAllowedFields(): array
    {
        return [];
    }

    public static function apiDocAdditionalIndexAllowedAppends(): array
    {
        return [];
    }

    public static function apiDocAdditionalShowAllowedFields(): array
    {
        return static::apiDocAdditionalIndexAllowedFields();
    }

    public static function apiDocAdditionalShowAllowedAppends(): array
    {
        return static::apiDocAdditionalIndexAllowedAppends();
    }

    public static function apiDocAdditionalAllowedSorts(): array
    {
        return [];
    }

    public static function apiDocAdditionalAllowedIncludes(): array
    {
        return [];
    }

    public static function apiDocAdditionalAllowedFilters(): array
    {
        return [];
    }

    public static function apiDocAllIndexAllowedFields(): array
    {
        return array_merge(
            static::apiDocIndexAllowedFields(),
            static::apiDocAdditionalIndexAllowedFields(),
        );
    }

    public static function apiDocAllIndexAllowedAppends(): array
    {
        return array_merge(
            static::apiDocIndexAllowedAppends(),
            static::apiDocAdditionalIndexAllowedAppends(),
        );
    }

    public static function apiDocAllShowAllowedFields(): array
    {
        return array_merge(
            static::apiDocShowAllowedFields(),
            static::apiDocAdditionalShowAllowedFields(),
        );
    }

    public static function apiDocAllShowAllowedAppends(): array
    {
        return array_merge(
            static::apiDocShowAllowedAppends(),
            static::apiDocAdditionalShowAllowedAppends(),
        );
    }

    public static function apiDocAllAllowedSorts(): array
    {
        return array_merge(
            static::apiDocAllowedSorts(),
            static::apiDocAdditionalAllowedSorts(),
        );
    }

    public static function apiDocAllAllowedIncludes(): array
    {
        return array_merge(
            static::apiDocAllowedIncludes(),
            static::apiDocAdditionalAllowedIncludes(),
        );
    }

    public static function apiDocAllAllowedFilters(): array
    {
        return array_merge(
            static::apiDocAllowedFilters(),
            static::apiDocAdditionalAllowedFilters(),
        );
    }

    public static function apiDocNewControllerInstance(): static
    {
        return app(static::class);
    }

    public static function apiDocDefaultSort(): string
    {
        $new_instance = static::apiDocNewControllerInstance();

        return $new_instance->getDefaultSort();
    }

    public static function apiDocAllowedFilters(): array
    {
        $new_instance = static::apiDocNewControllerInstance();

        return $new_instance->getAllowedFilters();
    }

    public static function apiDocAllowedIncludes(): array
    {
        $new_instance = static::apiDocNewControllerInstance();

        return $new_instance->getAllowedIncludes();
    }

    public static function apiDocIndexAllowedFields(): array
    {
        $new_instance = static::apiDocNewControllerInstance();

        return array_merge($new_instance->getIndexAllowedFields(), $new_instance->getAllowedAppendAttributes());
    }

    public static function apiDocIndexAllowedAppends(): array
    {
        $new_instance = static::apiDocNewControllerInstance();

        return $new_instance->getAllowedAppendAttributes();
    }

    public static function apiDocShowAllowedFields(): array
    {
        $new_instance = static::apiDocNewControllerInstance();

        return array_merge($new_instance->getAllowedFields(), $new_instance->getShowAllowedAppendAttributes());
    }

    public static function apiDocShowAllowedAppends(): array
    {
        $new_instance = static::apiDocNewControllerInstance();

        return $new_instance->getShowAllowedAppendAttributes();
    }

    public static function apiDocAllowedSorts(): array
    {
        $new_instance = static::apiDocNewControllerInstance();

        return $new_instance->getAllowedSorts();
    }

    public static function apiDocDefaultPerPage(): int
    {
        $new_instance = static::apiDocNewControllerInstance();

        return $new_instance->getDefaultPerPage();
    }

    public static function apiDocMaxPerPage(): int
    {
        $new_instance = static::apiDocNewControllerInstance();

        return $new_instance->getMaxPerPage();
    }

    public static function apiDocAllowUnlimitedResultsPerPage(): bool
    {
        $new_instance = static::apiDocNewControllerInstance();

        return $new_instance->allowUnlimitedResultsPerPage();
    }

    public static function apiDefaultFieldsDescription(): string
    {
        return 'Fields to include in the response. ' .
            'You can provide the fields as a comma-separated list, or provide the fields as an array query parameter i.e `fields[]`. ' .
            'By default all fields are included if the `fields` parameter is missing.';
    }

    public static function apiFieldsDescription(): string
    {
        return static::apiDefaultFieldsDescription();
    }

    public static function apiDocGenerateFieldsMetadata(array $fields): array
    {
        return [
            'type' => 'string',
            'description' => static::apiFieldsDescription() .
                '<br><br> **Allowed values:** ' . "\n" . implode("\n", array_map(fn($field) => "- `$field`", $fields)),
            'enum' => $fields,
            'example' => implode(',', $fields),
        ];
    }

    public static function apiDefaultSortsDescription(): string
    {
        return 'Which fields to sort the results by. ' .
            '<br>To sort in descending order, append a `-` to the field name, e.g. `?sort=-created_at`. ' .
            '<br>To sort by multiple fields, provide a comma-separated list, e.g. `?sort=id,-created_at`. ';
    }

    public static function apiSortsDescription(): string
    {
        return static::apiDefaultSortsDescription();
    }

    public static function apiDocGenerateSortsMetadata(array $sorts, string $default_sort = ''): array
    {
        return [
            'type' => 'string',
            'description' => static::apiSortsDescription() .
                '<br><br>**Allowed sorts:** ' . "\n" . implode("\n", array_map(fn($field) => "- `$field`", $sorts)) . "\n\n" .
                '<br>**Default sort:** ' . ($default_sort ? '`' . $default_sort . '`' : 'None'),
            'enum' => static::apiDocAllowedSorts(),
            'example' => static::apiDocDefaultSort(),
        ];
    }

    public static function apiDefaultAppendsDescription(): string
    {
        return 'Model accessor fields to include in the response. ' .
            'You can provide the append field as a comma-separated list, or provide the fields as an array query parameter i.e `append[]`. ' .
            'By default all appends are included if the `append` parameter is missing. ' .
            'To not append any fields, provide an empty string `?append=`';
    }

    public static function apiAppendsDescription(): string
    {
        return static::apiDefaultAppendsDescription();
    }

    public static function apiDocGenerateAppendsMetadata(array $appends): array
    {
        return [
            'type' => 'string',
            'description' => static::apiAppendsDescription() .
                '<br><br> **Allowed values:** ' . "\n" . implode("\n", array_map(fn($field) => "- `$field`", $appends)),
            'enum' => $appends,
            'example' => implode(',', $appends),
        ];
    }

    public static function apiDefaultIncludesDescription(): string
    {
        return 'Model relations to include in the response. ' .
            'You can provide the includes as a comma-separated list, or provide the includes as an array query parameter i.e `include[]`. ' .
            'By default all includes are included if the `include` parameter is missing. ' .
            'To not include any relations, provide an empty string `?include=`';
    }

    public static function apiIncludesDescription(): string
    {
        return static::apiDefaultIncludesDescription();
    }

    public static function apiDocGenerateIncludesMetadata(array $includes): array
    {
        return [
            'type' => 'string',
            'description' => static::apiIncludesDescription() . '<br><br> **Allowed values:** ' . "\n" . implode("\n", array_map(fn($field) => "- `$field`", $includes)),
            'enum' => $includes,
            'example' => implode(',', $includes),
        ];
    }

    public static function apiDocGeneratePerPageMetadata(): array
    {
        return [
            'type' => 'integer',
            'description' => 'How many results to return per page. ' .
                (static::apiDocAllowUnlimitedResultsPerPage() ? '<br></br>To return all results, set `per_page` to `-1`' : '') .
                '<br>**Max per page:** ' . static::apiDocMaxPerPage() .
                '<br>**Default per page:** ' . static::apiDocDefaultPerPage(),
            'example' => static::apiDocDefaultPerPage(),
        ];
    }

    public static function apiDocGeneratePageMetadata(): array
    {
        return [
            'type' => 'integer',
            'description' => 'For paginated results, which page to return.',
            'example' => 1,
        ];
    }

    public static function apiDocGenerateFilterMetadata(
        string               $filter_name,
        string|AllowedFilter $filter,
        string               $singular_resource_name,
        array                $metadata = [],
    ): array
    {
        $filter_title = Str::of($filter_name)
            ->replaceMatches('/([a-z])([A-Z])/', '$1 $2') // Split camelCase: pEnding → p Ending
            ->replace('_', ' ')                           // Replace custom separator (e.g. _ or space) with space
            ->lower()                                     // Lowercase everything
            ->title()                                     // Capitalize words
            ->toString();

        return array_merge([
            'type' => 'string',
            'description' => is_string($filter) ? 'Filter by the ' . Str::lower($filter_title) . ' of the ' . $singular_resource_name : 'Apply the ' . Str::lower($filter_title) . ' filter',
        ], $metadata);
    }


    public static function apiDocDefaultQueryParameters(
        array  $fields = [],
        array  $sorts = [],
        string $default_sort = '',
        array  $appends = [],
        array  $includes = [],
        array  $filters = [],
        array  $filter_metadata = [],
        bool   $include_pagination = false
    ): array
    {
        $params = [];

        if ($fields) {
            $params['fields'] = static::apiDocGenerateFieldsMetadata($fields);
        }

        if ($sorts) {
            $params['sort'] = static::apiDocGenerateSortsMetadata($sorts, $default_sort);
        }

        if ($appends) {
            $params['append'] = static::apiDocGenerateAppendsMetadata($appends);
        }

        if ($includes) {
            $params['include'] = static::apiDocGenerateIncludesMetadata($includes);
        }

        if ($include_pagination) {
            $params['per_page'] = static::apiDocGeneratePerPageMetadata();

            $params['page'] = static::apiDocGeneratePageMetadata();
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

                if (!$filter_name) {
                    continue;
                }

                $metadata = $filter_metadata[$filter_name] ?? [];

                $params["filter[{$filter_name}]"] = static::apiDocGenerateFilterMetadata(
                    $filter_name,
                    $filter,
                    $singular_resource_name,
                    $metadata,
                );
            }
        }

        return $params;
    }

    public static function apiDocDefaultIndexQueryParameters(): array
    {
        return static::apiDocDefaultQueryParameters(
            static::apiDocAllIndexAllowedFields(),
            static::apiDocAllAllowedSorts(),
            static::apiDocDefaultSort(),
            static::apiDocAllIndexAllowedAppends(),
            static::apiDocAllAllowedIncludes(),
            static::apiDocAllAllowedFilters(),
            static::apiDocFilterMetadata(),
            true
        );
    }

    public static function apiDocDefaultShowQueryParameters(): array
    {
        return static::apiDocDefaultQueryParameters(
            fields: static::apiDocAllShowAllowedFields(),
            appends: static::apiDocAllShowAllowedAppends(),
            includes: static::apiDocAllAllowedIncludes()
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
        return array_merge(
            static::apiDocDefaultIndexQueryParameters(),
            static::apiDocAdditionalIndexQueryParameters(),
        );
    }

    public static function apiDocShowQueryParameters(): array
    {
        return array_merge(
            static::apiDocDefaultShowQueryParameters(),
            static::apiDocAdditionalShowQueryParameters()
        );
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
