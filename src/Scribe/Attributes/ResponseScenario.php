<?php

declare(strict_types=1);

namespace Javaabu\QueryBuilder\Scribe\Attributes;

use Attribute;
use Javaabu\QueryBuilder\Scribe\Contracts\ResponseScenarioSetup;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ResponseScenario
{
    /**
     * Using a class string means this works with both attributes and apiDocScenarios().
     *
     * @param  array<string, scalar|null>  $url
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>  $query
     * @param  array<string, string>  $files
     * @param  array<string, string>  $cookies
     * @param  array<string, mixed>  $config
     * @param  class-string<ResponseScenarioSetup>|list<class-string<ResponseScenarioSetup>>|null  $setup
     * @param  array<string, mixed>  $setup_data
     */
    public function __construct(
        public string $name,
        public array $url = [],
        public array $body = [],
        public array $query = [],
        public array $files = [],
        public array $cookies = [],
        public array $config = [],
        public ?int $expected_status = null,
        public ?string $description = null,
        public bool $without_authentication = false,
        public string|array|null $setup = null,
        public array $setup_data = [],
    ) {}

    /**
     * @return list<class-string<ResponseScenarioSetup>>
     */
    public function setups(): array
    {
        return match (true) {
            $this->setup === null => [],
            is_string($this->setup) => [$this->setup],
            default => array_values($this->setup),
        };
    }
}
