---
title: Setting up Scribe
sidebar_position: 1
---

This package supports automatically generating API docs using [Scribe](https://github.com/knuckleswtf/scribe/).
Before you can generate API docs, you need to first properly setup Scribe.

## Install Scribe

To get started, first install Scribe.

```bash
composer require knuckleswtf/scribe
```

## Publish Scribe Config

Then publish the Scribe config.

```bash
php artisan vendor:publish --tag=scribe-config
```

## Add custom Scribe Strategies

Now add the following Strategies provided by this package to the `scribe.php` config file.

```php
// in scribe.php config file
'strategies' => [
    'metadata' => [
        ...Defaults::METADATA_STRATEGIES,
        \Javaabu\QueryBuilder\Scribe\Strategies\MetadataStrategy::class, // add this to metadata strategies
    ],
    
    ..
    
    'queryParameters' => [
        ...Defaults::QUERY_PARAMETERS_STRATEGIES,
        \Javaabu\QueryBuilder\Scribe\Strategies\QueryParametersStrategy::class, // add this to query parameter strategies
    ],
],
```

## Generate response scenarios

The package also includes `ResponseScenarioCalls` for generating multiple real
responses per endpoint. These classes require Scribe 5.3 or later; Scribe remains
an optional dependency of Query Builder.

Replace Scribe's default `ResponseCalls` strategy in `config/scribe.php`:

```php
use Javaabu\QueryBuilder\Scribe\Strategies\ResponseScenarioCalls;
use Knuckles\Scribe\Config\Defaults;
use Knuckles\Scribe\Extracting\Strategies\Responses\ResponseCalls;

'strategies' => [
    // Keep your other extraction stages.
    'responses' => [
        ...array_filter(
            Defaults::RESPONSES_STRATEGIES,
            static fn (string $strategy): bool => $strategy !== ResponseCalls::class,
        ),
        ResponseScenarioCalls::withSettings(config: ['app.debug' => false]),
    ],
],
```

Declare scenarios with repeatable method attributes or an `apiDocScenarios()`
provider keyed by controller action name. Providers may be public static methods
or public instance methods resolved through Laravel's container. Each action
accepts a single scenario or a list; attributes and provider scenarios are combined.

```php
use Javaabu\QueryBuilder\Scribe\Attributes\ResponseScenario;

public static function apiDocScenarios(): array
{
    return [
        'store' => [
            new ResponseScenario(
                name: 'Created',
                body: ['name' => 'Island Life'],
                expected_status: 201,
            ),
            new ResponseScenario(
                name: 'Validation failed',
                body: [],
                expected_status: 422,
            ),
        ],
    ];
}

#[ResponseScenario(name: 'Not found', url: ['id' => 999999], expected_status: 404)]
#[ResponseScenario(name: 'Unauthenticated', without_authentication: true, expected_status: 401)]
public function show(string $id)
{
    // Your endpoint implementation.
}
```

The constructor supports `url`, `body`, `query`, `files` (local upload paths),
`cookies`, `config`, `expected_status`, `description`, `without_authentication`,
`setup`, and `setup_data`. Body and file input replace extracted examples so an
empty body can exercise validation. Query, cookie, and config overrides merge with
global response-call settings. URL keys must match route placeholders, including
optional placeholders. Each scenario uses a cloned endpoint, preserving the
documented example URL.

The response description defaults to `name`. Give scenarios meaningful names,
and store them as lists so scenarios sharing an HTTP status are retained. A status
mismatch or an unsuccessful explicit response call fails generation. Explicit
scenarios run for any HTTP method; endpoints without scenarios retain Scribe's
GET-only fallback and existing-success-response behavior.

### Prepare scenario state

Keep application-specific setup classes in `app/Support/Scribe/Setups` and
implement the package contract:

```php
namespace App\Support\Scribe\Setups;

use App\Models\Product;
use Illuminate\Http\Request;
use Javaabu\QueryBuilder\Scribe\Attributes\ResponseScenario;
use Javaabu\QueryBuilder\Scribe\Contracts\ResponseScenarioSetup;
use Knuckles\Camel\Extraction\ExtractedEndpointData;

class CreateProductSetup implements ResponseScenarioSetup
{
    public function __invoke(
        Request $request,
        ExtractedEndpointData $endpoint_data,
        ResponseScenario $scenario,
    ): void {
        Product::factory()->create(['name' => $scenario->setup_data['name']]);
    }
}
```

Set `setup: CreateProductSetup::class` and `setup_data: ['name' => 'Island Life']`
on a scenario, or pass an ordered list of setup classes. Setups are container
resolved, receive the same scenario data, and run after Scribe's
`beforeResponseCall` hook inside its database transaction. Authentication guards
are cleared between calls; `without_authentication` removes authorization headers
even when a hook or setup adds them.

List every mutated connection in `database_connections_to_transact`. Use a
documentation database and fake external effects such as mail, notifications,
queues, payments, and filesystem writes; database rollback cannot undo them.

### Expose named examples in OpenAPI

To make same-status scenarios selectable in external UIs such as Scalar, register
the package generator after any other custom OpenAPI generators:

```php
'openapi' => [
    'enabled' => true,
    'overrides' => [],
    'generators' => [
        // Your other generators first.
        \Javaabu\QueryBuilder\Scribe\ResponseExamplesOpenApiGenerator::class,
    ],
],
```

It preserves generated schemas and adds uniquely named examples under
`responses.<status>.content.<media-type>.examples`. Binary bodies are skipped;
JSON is decoded and plain text is retained. OAuth grant schemas and application
setup classes remain application customizations.

After generation, check `.scribe/endpoints` for all scenarios and `openapi.yaml`
for their named examples. Generate twice to check that no scenario state leaks.

## Configure Auth

You would most probably need to configure auth for Scribe. Add the following recommended auth config to `scribe.php` config file.

```php
// How is your API authenticated? This information will be used in the displayed docs, generated examples and response calls.
'auth' => [
    // Set this to true if ANY endpoints in your API use authentication.
    'enabled' => true,

    // Set this to true if your API should be authenticated by default. If so, you must also set `enabled` (above) to true.
    // You can then use @unauthenticated or @authenticated on individual endpoints to change their status from the default.
    'default' => true,

    // Where is the auth value meant to be sent in a request?
    'in' => AuthIn::BEARER->value,

    // The name of the auth parameter (e.g. token, key, apiKey) or header (e.g. Authorization, Api-Key).
    'name' => 'Authorization',

    // Generate an access token / API key and add to the .env file
    'use_value' => env('SCRIBE_AUTH_KEY'),

    // Placeholder your users will see for the auth parameter in the example requests.
    // Set this to null if you want Scribe to use a random value as placeholder instead.
    'placeholder' => '{OAUTH_ACCESS_TOKEN}',

    // Add instructions on how to get the access token
    'extra_info' => 'You can retrieve your access token by visiting your profile in the dashboard and clicking <b>New API Token</b>. '.
        'Only users that have the "Generate Personal Access Token" permission will be able to generate new access tokens.',
],
```

Then add the access token to the `.env` file for Scribe to use.

```dotenv
SCRIBE_AUTH_KEY=your-access-token
```

## Generate API Docs

That's it! Now when you just need to run.

```bash
php artisan scribe:generate
```

And your API docs will be magically created with sensible documentation.

