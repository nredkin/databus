<?php

use App\Domain\Enums\MessageCode;
use App\Domain\Enums\MessageStatus;
use App\Models\Message;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\View;

/**
 * Render the monitoring page against a fixed set of messages instead of a database query.
 *
 * The Blade file only queries the database when no 'messages' paginator is passed in,
 * which keeps this test free of any database dependency.
 *
 * @param  array<int, Message>  $messages
 */
function renderMonitoringPage(array $messages): string
{
    $paginator = new LengthAwarePaginator(
        $messages,
        count($messages),
        50,
        1,
        ['path' => LengthAwarePaginator::resolveCurrentPath()],
    );

    return View::make('monitoring', ['messages' => $paginator])->render();
}

function makeMessage(string $payload): Message
{
    $message = new Message([
        'payload' => $payload,
        'message_code' => MessageCode::None->name,
        'message_id' => 'test-message-id',
    ]);
    $message->status = MessageStatus::Success->value;

    return $message;
}

it('renders a View trigger for every message', function () {
    $html = renderMonitoringPage([
        makeMessage('{"Name":"Widget"}'),
        makeMessage('{"Name":"Gadget"}'),
    ]);

    expect(substr_count($html, 'class="link-btn js-view-payload"'))->toBe(2)
        ->and(substr_count($html, '>View</button>'))->toBe(2)
        ->and($html)->toContain(e('{"Name":"Widget"}'))
        ->and($html)->toContain(e('{"Name":"Gadget"}'));
});

it('renders a single modal that is hidden by default and has Close buttons', function () {
    $html = renderMonitoringPage([makeMessage('{"a":1}')]);

    expect(substr_count($html, 'id="payload-modal"'))->toBe(1)
        ->and($html)->toContain('aria-modal="true"')
        ->and($html)->toContain('id="payload-modal-content"')
        ->and(substr_count($html, '>Close</button>'))->toBe(2)
        ->and($html)->toMatch('/id="payload-modal"\s+hidden/');
});

it('escapes untrusted payloads in the markup but keeps the data attribute lossless', function () {
    $payload = '{"html":"<script>alert(1)</script>","quote":"say \"hi\" & bye"}';

    $html = renderMonitoringPage([makeMessage($payload)]);

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->and($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');

    preg_match('/data-payload="([^"]*)"/', $html, $matches);

    expect($matches[1] ?? null)->not->toBeNull()
        ->and(html_entity_decode($matches[1], ENT_QUOTES))->toBe($payload);
});

it('keeps the full payload in the markup while the visible cell stays truncated', function () {
    $html = renderMonitoringPage([makeMessage(json_encode([
        'detail' => str_repeat('x', 500),
    ], JSON_THROW_ON_ERROR))]);

    expect($html)->toContain(str_repeat('x', 500))
        ->and(preg_match('/<span>[^<]*\.\.\.<\/span>/', $html))->toBe(1);
});

it('wires up pretty printing, backdrop clicks and the escape key', function () {
    $html = renderMonitoringPage([]);

    expect($html)->toContain('JSON.stringify(JSON.parse(raw), null, 2)')
        ->and($html)->toContain('event.target === overlay')
        ->and($html)->toContain("event.key === 'Escape'")
        ->and($html)->toContain('content.textContent =');
});

it('renders the table headers and no View trigger when there are no messages', function () {
    $html = renderMonitoringPage([]);

    expect($html)->toContain('Ser.ID')
        ->and($html)->toContain('Payload')
        ->and($html)->toContain('<tbody>')
        ->and($html)->not->toContain('class="link-btn js-view-payload"');
});
