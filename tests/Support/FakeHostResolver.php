<?php

namespace Tests\Support;

use App\Domain\Services\HostResolver;

/**
 * Deterministic HostResolver for tests: no real DNS lookups are performed.
 */
class FakeHostResolver extends HostResolver
{
    /**
     * @param  array<string, array<int, string>>  $map  Host => list of IP addresses.
     */
    public function __construct(private readonly array $map = []) {}

    public function resolve(string $host): array
    {
        return $this->map[$host] ?? ['93.184.216.34'];
    }
}
