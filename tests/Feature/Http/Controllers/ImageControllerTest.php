<?php

use App\Domain\Services\HostResolver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakeHostResolver;

uses(RefreshDatabase::class);

const IMAGE_URL = 'https://images.example.com/photo.png';

/** 1x1 PNG, binary-safe by design. */
function pngBytes(): string
{
    return base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg=='
    );
}

function bindResolver(array $map = []): void
{
    app()->instance(HostResolver::class, new FakeHostResolver($map));
}

function authenticatedUser(): User
{
    return User::factory()->create([
        'email' => 'api@example.com',
        'code' => 'APIUSER',
        'password' => 'secret-password',
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function postImage(array $overrides = [])
{
    return test()->withBasicAuth('api@example.com', 'secret-password')
        ->postJson('/api/v1/images/fetch', $overrides);
}

beforeEach(function () {
    bindResolver();
    authenticatedUser();
});

it('returns the fetched image bytes with the upstream content type', function () {
    Http::fake([IMAGE_URL => Http::response(pngBytes(), 200, ['Content-Type' => 'image/png'])]);

    $response = postImage(['URL' => IMAGE_URL]);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('image/png')
        ->and($response->headers->get('Content-Disposition'))->toBe('inline')
        ->and($response->getContent())->toBe(pngBytes());
});

it('returns binary content unchanged', function () {
    $bytes = random_bytes(2048);
    Http::fake([IMAGE_URL => Http::response($bytes, 200, ['Content-Type' => 'image/jpeg'])]);

    $response = postImage(['URL' => IMAGE_URL]);

    $response->assertOk();
    expect(strlen($response->getContent()))->toBe(2048)
        ->and($response->getContent())->toBe($bytes)
        ->and($response->headers->get('Content-Type'))->toStartWith('image/jpeg');
});

it('keeps the request body field name case sensitive', function () {
    Http::fake();

    postImage(['url' => IMAGE_URL])
        ->assertStatus(422)
        ->assertJsonPath('code', 422);

    Http::assertNothingSent();
});

it('rejects a request without a URL', function () {
    Http::fake();

    postImage()
        ->assertStatus(422)
        ->assertJsonPath('result', null)
        ->assertJsonPath('code', 422);

    Http::assertNothingSent();
});

it('rejects a malformed URL', function () {
    Http::fake();

    postImage(['URL' => 'definitely not a url'])
        ->assertStatus(400)
        ->assertJsonPath('code', 400);

    Http::assertNothingSent();
});

it('rejects a non http scheme', function (string $url) {
    Http::fake();

    postImage(['URL' => $url])
        ->assertStatus(400)
        ->assertJsonPath('code', 400);

    Http::assertNothingSent();
})->with([
    'file' => 'file:///etc/passwd',
    'ftp' => 'ftp://images.example.com/photo.png',
    'data' => 'data:image/png;base64,AAAA',
]);

it('blocks private and reserved addresses', function (string $address) {
    Http::fake();
    bindResolver(['internal.example.com' => [$address]]);

    postImage(['URL' => 'https://internal.example.com/photo.png'])
        ->assertStatus(403)
        ->assertJsonPath('code', 403);

    Http::assertNothingSent();
})->with([
    'loopback' => '127.0.0.1',
    'private class A' => '10.0.0.5',
    'private class C' => '192.168.1.10',
    'link local metadata' => '169.254.169.254',
    'cgnat' => '100.64.0.1',
    'ipv6 loopback' => '::1',
    'ipv6 unique local' => 'fd00::1',
]);

it('blocks the request when any resolved address is private', function () {
    Http::fake();
    bindResolver(['mixed.example.com' => ['93.184.216.34', '10.0.0.1']]);

    postImage(['URL' => 'https://mixed.example.com/photo.png'])
        ->assertStatus(403);

    Http::assertNothingSent();
});

it('returns an error when the host cannot be resolved', function () {
    Http::fake();
    bindResolver(['missing.example.com' => []]);

    postImage(['URL' => 'https://missing.example.com/photo.png'])
        ->assertStatus(502);
});

it('returns 502 when the upstream responds with an error', function (int $status) {
    Http::fake([IMAGE_URL => Http::response('upstream error', $status)]);

    postImage(['URL' => IMAGE_URL])
        ->assertStatus(502)
        ->assertJsonPath('code', 502);
})->with([404, 500, 503]);

it('rejects a response that is not an image', function () {
    Http::fake([IMAGE_URL => Http::response('<html></html>', 200, ['Content-Type' => 'text/html'])]);

    postImage(['URL' => IMAGE_URL])
        ->assertStatus(415)
        ->assertJsonPath('code', 415);
});

it('rejects an oversized image', function () {
    config()->set('images.max_bytes', 1024);
    Http::fake([IMAGE_URL => Http::response(str_repeat('a', 4096), 200, ['Content-Type' => 'image/png'])]);

    postImage(['URL' => IMAGE_URL])
        ->assertStatus(413)
        ->assertJsonPath('code', 413);
});

it('rejects an oversized image even when Content-Length lies', function () {
    config()->set('images.max_bytes', 1024);
    Http::fake([IMAGE_URL => Http::response(str_repeat('a', 4096), 200, [
        'Content-Type' => 'image/png',
        'Content-Length' => '10',
    ])]);

    postImage(['URL' => IMAGE_URL])
        ->assertStatus(413);
});

it('accepts an image exactly at the size limit', function () {
    config()->set('images.max_bytes', 1024);
    Http::fake([IMAGE_URL => Http::response(str_repeat('a', 1024), 200, ['Content-Type' => 'image/png'])]);

    postImage(['URL' => IMAGE_URL])->assertOk();
});

it('returns 504 when the upstream connection times out', function () {
    Http::fake([IMAGE_URL => fn () => throw new ConnectionException('cURL error 28: Operation timed out')]);

    postImage(['URL' => IMAGE_URL])
        ->assertStatus(504)
        ->assertJsonPath('code', 504);
});

it('requires authentication', function () {
    Http::fake();

    $this->postJson('/api/v1/images/fetch', ['URL' => IMAGE_URL])
        ->assertStatus(401);

    Http::assertNothingSent();
});
