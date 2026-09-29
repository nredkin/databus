<?php

use App\Domain\Services\IpGuard;

it('allows public addresses', function (string $address) {
    expect((new IpGuard)->isPublic($address))->toBeTrue();
})->with([
    'ipv4' => '93.184.216.34',
    'dns' => '8.8.8.8',
    'ipv6' => '2606:2800:220:1:248:1893:25c8:1946',
    'just outside private range' => '172.32.5.5',
    'just outside cgnat' => '100.128.0.1',
    'ipv4 mapped public' => '::ffff:93.184.216.34',
]);

it('blocks non-public addresses', function (string $address) {
    expect((new IpGuard)->isPublic($address))->toBeFalse();
})->with([
    'loopback' => '127.0.0.1',
    'loopback shorthand' => '127.1.2.3',
    'private class A' => '10.1.2.3',
    'private class B' => '172.16.5.5',
    'private class C' => '192.168.0.1',
    'link local metadata' => '169.254.169.254',
    'this network' => '0.0.0.0',
    'this network range' => '0.1.2.3',
    'cgnat' => '100.64.0.1',
    'test net 1' => '192.0.2.5',
    'benchmarking' => '198.18.0.1',
    'test net 2' => '198.51.100.7',
    'test net 3' => '203.0.113.9',
    'reserved' => '240.0.0.1',
    'broadcast' => '255.255.255.255',
    'ipv6 loopback' => '::1',
    'ipv6 unique local' => 'fd00::1',
    'ipv6 link local' => 'fe80::1',
    'ipv6 unspecified' => '::',
    'ipv6 documentation' => '2001:db8::1',
    'ipv4 mapped loopback' => '::ffff:127.0.0.1',
    'ipv4 mapped private' => '::ffff:10.0.0.1',
    'not an address' => 'not-an-ip',
    'empty' => '',
]);
