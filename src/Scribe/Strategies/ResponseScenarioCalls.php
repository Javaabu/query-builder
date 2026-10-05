<?php

declare(strict_types=1);

namespace Javaabu\QueryBuilder\Scribe\Strategies;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Javaabu\QueryBuilder\Scribe\Attributes\ResponseScenario;
use Javaabu\QueryBuilder\Scribe\Contracts\ResponseScenarioSetup;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\Responses\ResponseCalls;
use ReflectionAttribute;
use ReflectionException;
use RuntimeException;
use UnexpectedValueException;

final class ResponseScenarioCalls extends ResponseCalls
{
    private ?ResponseScenario $current_scenario = null;

    /**
     * Indicates that the currently executing scenario must not contain an
     * Authorization header.
     */
    private bool $without_authentication = false;

    /**
     * Scribe runs request preparation outside its cleanup block. Restore state
     * even when preparing a file, running a hook, or resolving a setup fails.
     *
     * @param  array<string, mixed>  $settings
     * @return list<array<string, mixed>>|null
     */
    public function makeResponseCall(ExtractedEndpointData $endpoint_data, array $settings): ?array
    {
        $connections = [];

        foreach ($this->getConfig()->get('database_connections_to_transact', []) as $connection_name) {
            $connection = app('db')->connection($connection_name);
            $connections[] = [$connection, $connection->transactionLevel()];
        }

        $this->previousConfigs = [];

        try {
            return parent::makeResponseCall($endpoint_data, $settings);
        } finally {
            foreach ($connections as [$connection, $transaction_level]) {
                if ($connection->transactionLevel() > $transaction_level) {
                    $connection->rollBack($transaction_level);
                }
            }

            foreach ($this->previousConfigs as $name => $value) {
                config([$name => $value]);
            }

            $this->previousConfigs = [];
            Auth::forgetGuards();
        }
    }

    /**
     * Generate responses for every ResponseScenario attribute on the endpoint.
     *
     * For endpoints without a ResponseScenario, preserve Scribe's default
     * behaviour of making a response call only for GET endpoints.
     *
     * @return array<int, array<string, mixed>>|null
     *
     * @throws ReflectionException
     */
    public function __invoke(
        ExtractedEndpointData $endpoint_data,
        array $settings = [],
    ): ?array {
        $scenarios = $this->scenariosForEndpoint($endpoint_data);

        /*
         * Preserve Scribe's normal GET response-call behaviour for endpoints
         * that do not use ResponseScenario.
         */
        if ($scenarios === []) {
            if (! in_array(
                'GET',
                $this->getMethods($endpoint_data->route),
                true,
            )) {
                return null;
            }

            return parent::__invoke($endpoint_data, $settings);
        }

        $responses = [];

        foreach ($scenarios as $scenario) {
            $scenario_endpoint_data = $this->endpointDataForScenario(
                $endpoint_data,
                $scenario,
            );

            $this->current_scenario = $scenario;
            $this->without_authentication = $scenario->without_authentication;

            try {
                $generated_responses = $this->makeResponseCall(
                    $scenario_endpoint_data,
                    $this->settingsForScenario($settings, $scenario),
                );
            } finally {
                $this->current_scenario = null;
                $this->without_authentication = false;
            }

            if ($generated_responses === null) {
                throw new RuntimeException(sprintf(
                    'Scribe could not generate the "%s" response scenario for [%s] %s.',
                    $scenario->name,
                    implode('|', $this->getMethods($endpoint_data->route)),
                    $endpoint_data->uri,
                ));
            }

            foreach ($generated_responses as $generated_response) {
                $this->validateStatus($scenario, $generated_response);

                $generated_response['description'] = $this->description(
                    $scenario,
                );

                $responses[] = $generated_response;
            }
        }

        return $responses;
    }

    /**
     * Collect scenarios declared as PHP attributes and scenarios returned by
     * the controller's optional apiDocScenarios() method.
     *
     * The apiDocScenarios() return value is keyed by controller action name,
     * such as show, store, update, destroy, or __invoke.
     *
     * @return array<int, ResponseScenario>
     *
     * @throws ReflectionException
     */
    private function scenariosForEndpoint(
        ExtractedEndpointData $endpoint_data,
    ): array {
        // Get all attribute scenarios
        $scenarios = array_map(
            static fn (
                ReflectionAttribute $attribute,
            ): ResponseScenario => $attribute->newInstance(),
            $endpoint_data->method->getAttributes(
                ResponseScenario::class,
                ReflectionAttribute::IS_INSTANCEOF,
            ),
        );

        $controller = $endpoint_data->controller;

        if (
            $controller === null
            || ! $controller->hasMethod('apiDocScenarios')
        ) {
            return $scenarios;
        }

        $provider = $controller->getMethod('apiDocScenarios');

        if (! $provider->isPublic()) {
            throw new InvalidArgumentException(sprintf(
                '%s::apiDocScenarios() must be public.',
                $controller->getName(),
            ));
        }

        /*
         * Static providers are recommended because they do not require a
         * controller instance. Non-static providers are also supported and
         * are resolved through Laravel's service container.
         */
        $controller_instance = $provider->isStatic()
            ? null
            : app($controller->getName());

        $definitions = $provider->invoke($controller_instance);

        if (! is_array($definitions)) {
            throw new InvalidArgumentException(sprintf(
                '%s::apiDocScenarios() must return an array.',
                $controller->getName(),
            ));
        }

        $action = $endpoint_data->method->getName();
        $action_scenarios = $definitions[$action] ?? [];

        if ($action_scenarios instanceof ResponseScenario) {
            $action_scenarios = [$action_scenarios];
        }

        if (! is_array($action_scenarios)) {
            throw new InvalidArgumentException(sprintf(
                'The scenarios for %s::%s must be a ResponseScenario or an array of ResponseScenario objects.',
                $controller->getName(),
                $action,
            ));
        }

        foreach ($action_scenarios as $index => $scenario) {
            if (! $scenario instanceof ResponseScenario) {
                throw new InvalidArgumentException(sprintf(
                    'Scenario %s for %s::%s must be an instance of %s.',
                    (string) $index,
                    $controller->getName(),
                    $action,
                    ResponseScenario::class,
                ));
            }

            $scenarios[] = $scenario;
        }

        return $scenarios;
    }

    /**
     * Run Scribe's configured beforeResponseCall hook, then enforce the
     * scenario's authentication setting.
     *
     * Removing the header after the hook is important because an application
     * hook may add an Authorization header unconditionally.
     */
    protected function runPreRequestHook(
        Request $request,
        ExtractedEndpointData $endpoint_data,
    ): void {
        /*
         * Scribe executes several HTTP requests within the same PHP process.
         * Forget guards resolved by an earlier scenario so their cached user
         * cannot leak into this request.
         */
        Auth::forgetGuards();

        /*
         * Runs Scribe's/AppServiceProvider's beforeResponseCall hook.
         * Your bearer token is attached here.
         */
        parent::runPreRequestHook($request, $endpoint_data);

        /*
         * Prepare the scenario-specific database state.
         */
        $this->prepareScenario($request, $endpoint_data);

        // Setups can issue a scenario-specific token or otherwise resolve auth.
        Auth::forgetGuards();

        if (! $this->without_authentication) {
            return;
        }

        $request->headers->remove('Authorization');
        $request->server->remove('HTTP_AUTHORIZATION');
        $request->server->remove('REDIRECT_HTTP_AUTHORIZATION');
        $request->setUserResolver(static fn () => null);

        /*
         * A beforeResponseCall hook may itself resolve a guard, so forget the
         * guards again after all pre-request hooks have completed.
         */
        Auth::forgetGuards();
    }

    private function prepareScenario(
        Request $request,
        ExtractedEndpointData $endpoint_data,
    ): void {
        $scenario = $this->current_scenario;

        if ($scenario === null) {
            return;
        }

        foreach ($scenario->setups() as $setup_class) {
            if (! is_string($setup_class) || $setup_class === '') {
                throw new InvalidArgumentException(sprintf(
                    'Every setup for the Scribe scenario "%s" must be a non-empty class-string.',
                    $scenario->name,
                ));
            }

            $setup = app($setup_class);

            if (! $setup instanceof ResponseScenarioSetup) {
                throw new InvalidArgumentException(sprintf(
                    'The Scribe scenario setup %s must implement %s.',
                    $setup_class,
                    ResponseScenarioSetup::class,
                ));
            }

            $setup($request, $endpoint_data, $scenario);
        }
    }

    /**
     * Clear resolved guards after every response call so authentication state
     * cannot leak into the next scenario.
     */
    protected function runPostRequestHook(
        Request $request,
        ExtractedEndpointData $endpoint_data,
        mixed $response,
    ): void {
        try {
            parent::runPostRequestHook(
                $request,
                $endpoint_data,
                $response,
            );
        } finally {
            Auth::forgetGuards();
        }
    }

    /**
     * Clone the extracted endpoint and apply scenario-specific route and
     * authentication changes.
     *
     * Route placeholders are replaced directly in the cloned URI. This changes
     * only the internal response call; it does not change the primary example
     * URL displayed for the endpoint.
     */
    private function endpointDataForScenario(
        ExtractedEndpointData $endpoint_data,
        ResponseScenario $scenario,
    ): ExtractedEndpointData {
        $scenario_endpoint_data = clone $endpoint_data;

        foreach ($scenario->url as $name => $value) {
            $pattern = sprintf(
                '/\{%s\??\}/',
                preg_quote((string) $name, '/'),
            );

            if (! preg_match($pattern, $scenario_endpoint_data->uri)) {
                throw new InvalidArgumentException(sprintf(
                    'The route parameter {%s} does not exist in the URI "%s".',
                    $name,
                    $scenario_endpoint_data->uri,
                ));
            }

            $scenario_endpoint_data->uri = preg_replace_callback(
                $pattern,
                static fn (): string => rawurlencode((string) $value),
                $scenario_endpoint_data->uri,
            );
        }

        if ($scenario->without_authentication) {
            /*
             * Scribe represents an endpoint without authentication using an
             * empty array. ResponseCalls checks this value as a boolean before
             * attempting to add authentication to the request.
             */
            $scenario_endpoint_data->auth = [];
        }

        $scenario_endpoint_data->cleanBodyParameters = [];
        $scenario_endpoint_data->fileParameters = [];

        return $scenario_endpoint_data;
    }

    /**
     * Merge scenario input with the response-call settings.
     *
     * Values declared on the scenario take precedence over global strategy
     * settings and examples extracted from the endpoint.
     *
     * @return array<string, mixed>
     */
    private function settingsForScenario(
        array $settings,
        ResponseScenario $scenario,
    ): array {
        $settings['bodyParams'] = $scenario->body;

        $settings['queryParams'] = array_replace_recursive(
            $settings['queryParams'] ?? [],
            $scenario->query,
        );

        $settings['fileParams'] = $scenario->files;

        $settings['cookies'] = array_replace_recursive(
            $settings['cookies'] ?? [],
            $scenario->cookies,
        );

        $settings['config'] = array_replace_recursive(
            $settings['config'] ?? [],
            $scenario->config,
        );

        return $settings;
    }

    /**
     * Verify that Laravel returned the status expected by the scenario.
     *
     * Omitting expected_status allows any status to be documented.
     *
     * @param  array<string, mixed>  $response
     */
    private function validateStatus(
        ResponseScenario $scenario,
        array $response,
    ): void {
        if ($scenario->expected_status === null) {
            return;
        }

        $actual_status = (int) $response['status'];

        if ($actual_status !== $scenario->expected_status) {
            throw new UnexpectedValueException(sprintf(
                'The Scribe scenario "%s" expected HTTP %d but received HTTP %d.',
                $scenario->name,
                $scenario->expected_status,
                $actual_status,
            ));
        }
    }

    /**
     * Resolve the description shown for the generated response.
     */
    private function description(ResponseScenario $scenario): string
    {
        return $scenario->description ?? $scenario->name;
    }
}
