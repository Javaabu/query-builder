<?php

declare(strict_types=1);

namespace Javaabu\QueryBuilder\Tests\Feature\Scribe\Strategies;

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Javaabu\QueryBuilder\Scribe\Attributes\ResponseScenario;
use Javaabu\QueryBuilder\Scribe\ResponseExamplesOpenApiGenerator;
use Javaabu\QueryBuilder\Scribe\Strategies\ResponseScenarioCalls;
use Javaabu\QueryBuilder\Tests\Controllers\InstanceResponseScenariosController;
use Javaabu\QueryBuilder\Tests\Controllers\ResponseScenariosController;
use Javaabu\QueryBuilder\Tests\InteractsWithDatabase;
use Javaabu\QueryBuilder\Tests\Scribe\Setups\CreateProductSetup;
use Javaabu\QueryBuilder\Tests\Scribe\Setups\RecordScenarioSetup;
use Javaabu\QueryBuilder\Tests\TestCase;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Camel\Extraction\Response;
use Knuckles\Camel\Output\OutputEndpointData;
use Knuckles\Scribe\Tools\DocumentationConfig;
use Knuckles\Scribe\Tools\Globals;
use Knuckles\Scribe\Writing\OpenAPISpecWriter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use UnexpectedValueException;

class ResponseScenarioCallsTest extends TestCase
{
    use InteractsWithDatabase;

    public function setUp(): void
    {
        parent::setUp();

        ResponseScenariosController::$definitions = [];
        Globals::$__beforeResponseCall = null;
        Globals::$__afterResponseCall = null;
        config([
            'auth.defaults.guard' => 'scenario',
            'auth.guards.scenario' => [
                'driver' => 'scenario',
            ]
        ]);
        Auth::viaRequest('scenario', static fn(Request $request): ?GenericUser => $request->bearerToken()
            ? new GenericUser(['id' => 7])
            : null);
    }

    protected function tearDown(): void
    {
        ResponseScenariosController::$definitions = [];
        Globals::$__beforeResponseCall = null;
        Globals::$__afterResponseCall = null;

        parent::tearDown();
    }

    #[Test]
    public function it_merges_repeatable_attributes_and_provider_scenarios_without_dropping_duplicate_statuses(): void
    {
        ResponseScenariosController::$definitions = ['attribute' => [
            new ResponseScenario(name: 'Provider response', body: ['source' => 'provider'], expected_status: 200),
            new ResponseScenario(name: 'Third response', body: ['source' => 'third'], description: 'Custom description'),
        ]];

        // The helper registers a `POST /scenarios` route pointing to `ResponseScenariosController::attribute()`, then returns Scribe’s endpoint data for that route
        $endpoint = $this->endpoint('POST', 'attribute');

        // This creates a `ResponseScenarioCalls` object and immediately calls it with `$endpoint`. PHP allows an object to be called like a function when it has an `__invoke()` method.
        $responses = ($this->strategy())($endpoint);

        $this->assertSame([200, 200, 200, 200], array_column($responses, 'status'));
        $this->assertSame(['Attribute response', 'Second attribute', 'Provider response', 'Custom description'], array_column($responses, 'description'));
        $this->assertSame(['attribute', 'second attribute', 'provider', 'third'], array_map(fn(array $response): string => $this->body($response)['body']['source'], $responses));
    }

    #[Test]
    public function it_resolves_instance_providers_and_single_scenarios_through_the_container(): void
    {
        $this->app->instance(ResponseScenario::class, new ResponseScenario(name: 'Injected scenario', body: ['name' => 'Aisha']));
        $route = Route::post('/instance', [InstanceResponseScenariosController::class, 'show']);
        $endpoint = ExtractedEndpointData::fromRoute($route);

        $responses = ($this->strategy())($endpoint);

//        [
//            0 => [
//                'status' => 200,
//                'description' => 'Injected scenario',
//                'content' => "{"body":{"name":"Aisha"}}"
//                'headers' => [...]
//            ],
//        ]
        $this->assertSame('Injected scenario', $responses[0]['description']);
        $this->assertSame(['body' => ['name' => 'Aisha']], $this->body($responses[0]));
    }

    #[Test]
    public function it_calls_get_endpoints_and_skips_writes_without_explicit_scenarios(): void
    {
        $strategy = $this->strategy();

        $get_responses = $strategy($this->endpoint('GET', 'show'));
        $post_responses = $strategy($this->endpoint('POST', 'store', '/writes'));

        $this->assertSame(200, $get_responses[0]['status']);
        $this->assertNull($post_responses);
    }

    #[Test]
    public function it_preserves_existing_success_responses_without_scenarios(): void
    {
        $endpoint = $this->endpoint('GET', 'show');
        // We are mimicking a documented response that was already present in the endpoint data before Scribe runs its scenario strategy
        $endpoint->responses->add(new Response(['status' => 200, 'content' => '{"documented":true}']));

        $responses = ($this->strategy())($endpoint);

        $this->assertNull($responses);
    }

    #[Test]
    public function it_executes_explicit_write_scenarios_and_rolls_back_every_request(): void
    {
        $this->runMigrations();
        ResponseScenariosController::$definitions = ['store' => [
            new ResponseScenario(name: 'Created', body: ['name' => 'Apple'], expected_status: 201),
            new ResponseScenario(name: 'Validation failed', body: [], expected_status: 422),
        ]];
        $endpoint = $this->endpoint('POST', 'store');
        $endpoint->cleanBodyParameters = ['name' => 'Extracted example'];

        $responses = ($this->strategy([config('database.default')]))($endpoint);

        $this->assertSame([201, 422], array_column($responses, 'status'));
        $this->assertSame(['name' => 'Apple'], $this->body($responses[0]));
        $this->assertArrayHasKey('name', $this->body($responses[1])['errors']);
        $this->assertDatabaseCount('products', 0); // Meaning it rolls back
        $this->assertSame(0, DB::connection()->transactionLevel());
    }

    #[Test]
    public function it_runs_setups_inside_each_scenario_transaction(): void
    {
        $this->runMigrations();
        ResponseScenariosController::$definitions = ['products' => [
            new ResponseScenario(name: 'Prepared', setup: CreateProductSetup::class, setup_data: ['name' => 'Apple']),
            new ResponseScenario(name: 'Empty'),
        ]];

        $responses = ($this->strategy([config('database.default')]))($this->endpoint('GET', 'products'));

        $this->assertSame(['names' => ['Apple']], $this->body($responses[0]));
        $this->assertSame(['names' => []], $this->body($responses[1]));
        $this->assertDatabaseCount('products', 0);
    }

    #[Test]
    public function it_generates_repeatable_named_examples_through_scribes_openapi_writer(): void
    {
        $this->runMigrations();
        ResponseScenariosController::$definitions = ['store' => [
            new ResponseScenario(name: 'Created', body: ['name' => 'Apple'], expected_status: 201),
            new ResponseScenario(name: 'Missing name', body: [], expected_status: 422),
            new ResponseScenario(name: 'Invalid name', body: ['name' => false], expected_status: 422),
        ]];
        $endpoint = $this->endpoint('POST', 'store');
        $strategy = $this->strategy([config('database.default')]);
        $writer = new OpenAPISpecWriter(new DocumentationConfig([
            'openapi' => ['generators' => [ResponseExamplesOpenApiGenerator::class]],
        ]));
        $specs = [];

        for ($generation = 0; $generation < 2; $generation++) {
            $responses = $strategy($endpoint);
            $output = new OutputEndpointData([
                ...$endpoint->forSerialisation(),
                'responses' => $responses,
            ]);
            $specs[] = $writer->generateSpecContent([
                ['name' => 'Products', 'description' => '', 'endpoints' => [$output]],
            ]);
        }

        $this->assertSame(json_encode($specs[0], JSON_THROW_ON_ERROR), json_encode($specs[1], JSON_THROW_ON_ERROR));
        $content = $specs[0]['paths']['/scenarios']['post']['responses'][422]['content']['application/json'];
        $this->assertCount(2, $content['schema']['oneOf']);
        $this->assertSame(['missing_name', 'invalid_name'], array_keys($content['examples']));
        $this->assertSame(['The name field is required.'], $content['examples']['missing_name']['value']['errors']['name']);
        $this->assertSame(['The name field must be a string.'], $content['examples']['invalid_name']['value']['errors']['name']);
        $this->assertDatabaseCount('products', 0);
    }

    #[Test]
    public function it_runs_ordered_setups_after_the_hook_and_isolates_unauthenticated_calls(): void
    {
        Globals::$__beforeResponseCall = static function (Request $request): void {
            $request->attributes->set('setups', ['hook']);
            $request->headers->set('Authorization', 'Bearer hook-token');
            $request->server->set('HTTP_AUTHORIZATION', 'Bearer hook-token');
            $request->server->set('REDIRECT_HTTP_AUTHORIZATION', 'Bearer hook-token');
            Auth::guard()->setUser(new GenericUser(['id' => 99]));
        };
        ResponseScenariosController::$definitions = ['show' => [
            new ResponseScenario(name: 'Authenticated', setup: RecordScenarioSetup::class, setup_data: ['marker' => 'setup']),
            new ResponseScenario(name: 'Anonymous', without_authentication: true, setup: [RecordScenarioSetup::class, RecordScenarioSetup::class], setup_data: ['marker' => 'setup']),
            new ResponseScenario(name: 'Authenticated again'),
        ]];

        $responses = ($this->strategy())($this->endpoint('GET', 'show'));

        $this->assertSame(['hook', 'setup'], $this->body($responses[0])['setups']);
        $this->assertSame('Bearer setup-token', $this->body($responses[0])['authorization']);
        $this->assertSame(7, $this->body($responses[0])['user']);
        $anonymous = $this->body($responses[1]);
        $this->assertSame(['hook', 'setup', 'setup'], $anonymous['setups']);
        foreach (['authorization', 'server_authorization', 'redirect_authorization', 'user'] as $key) {
            $this->assertNull($anonymous[$key]);
        }
        $this->assertSame(7, $this->body($responses[2])['user']);
    }

    #[Test]
    public function it_rejects_unknown_route_parameters(): void
    {
        ResponseScenariosController::$definitions = ['show' => new ResponseScenario(name: 'Invalid URL', url: ['missing' => 1])];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The route parameter {missing} does not exist');

        ($this->strategy())($this->endpoint('GET', 'show'));
    }

    #[Test]
    public function it_fails_on_a_status_mismatch_after_restoring_configuration(): void
    {
        $original_name = config('app.name');
        ResponseScenariosController::$definitions = ['show' => new ResponseScenario(name: 'Mismatch', expected_status: 404, config: ['app.name' => 'Temporary'])];

        try {
            ($this->strategy())($this->endpoint('GET', 'show'));
            $this->fail('A mismatched status must fail generation.');
        } catch (UnexpectedValueException $exception) {
            $this->assertSame('The Scribe scenario "Mismatch" expected HTTP 404 but received HTTP 200.', $exception->getMessage());
        }

        $this->assertSame($original_name, config('app.name'));
    }

    #[Test]
    #[DataProvider('invalidDefinitions')]
    public function it_rejects_invalid_provider_definitions(mixed $definitions, string $message): void
    {
        ResponseScenariosController::$definitions = $definitions;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        ($this->strategy())($this->endpoint('GET', 'show'));
    }

    /** @return array<string, array{mixed, string}> */
    public static function invalidDefinitions(): array
    {
        return [
            'provider return type' => ['invalid', 'must return an array'],
            'action return type' => [['show' => 'invalid'], 'must be a ResponseScenario or an array'],
            'scenario type' => [['show' => ['invalid']], 'must be an instance of'],
        ];
    }

    #[Test]
    #[DataProvider('invalidSetups')]
    public function it_rejects_invalid_setups_and_restores_the_open_transaction(mixed $setup, string $message): void
    {
        $this->runMigrations();
        $original_name = config('app.name');
        ResponseScenariosController::$definitions = ['products' => new ResponseScenario(
            name: 'Invalid setup',
            setup: [CreateProductSetup::class, $setup],
            setup_data: ['name' => 'Apple'],
            config: ['app.name' => 'Temporary'],
        )];

        try {
            ($this->strategy([config('database.default')]))($this->endpoint('GET', 'products'));
            $this->fail('An invalid setup must fail generation.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
        }

        $this->assertSame($original_name, config('app.name'));
        $this->assertSame(0, DB::connection()->transactionLevel());
        $this->assertDatabaseCount('products', 0);
    }

    /** @return array<string, array{mixed, string}> */
    public static function invalidSetups(): array
    {
        return [
            'empty class string' => ['', 'must be a non-empty class-string'],
            'non-string class' => [123, 'must be a non-empty class-string'],
            'wrong contract' => [\stdClass::class, 'must implement'],
        ];
    }

    #[Test]
    public function it_fails_when_an_explicit_response_call_cannot_be_generated(): void
    {
        Globals::$__afterResponseCall = static function (): void {
            throw new RuntimeException('Failed post-request hook');
        };
        ResponseScenariosController::$definitions = ['show' => new ResponseScenario(name: 'Failed response')];
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Scribe could not generate the "Failed response" response scenario');

        ($this->strategy())($this->endpoint('GET', 'show'));
    }

    /** @param list<string> $connections */
    private function strategy(array $connections = []): ResponseScenarioCalls
    {
        // Telling the strategy **which database connections to wrap in transactions**.
        // Each response call starts a transaction on those connections and rolls it back afterward,
        //  undoing records created by the endpoint or its scenario setups.
        // An empty array means Scribe opens no database transactions.
        return new ResponseScenarioCalls(new DocumentationConfig(['database_connections_to_transact' => $connections]));
    }

    private function endpoint(string $method, string $action, string $uri = '/scenarios'): ExtractedEndpointData
    {
        $route = Route::match([$method], $uri, [ResponseScenariosController::class, $action]);

        return ExtractedEndpointData::fromRoute($route, ['headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json']]);
    }

    /**
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    private function body(array $response): array
    {
        return json_decode($response['content'], true, flags: JSON_THROW_ON_ERROR);
    }
}
