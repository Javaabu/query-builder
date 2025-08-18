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

## Add custom Sribe Strategies

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


