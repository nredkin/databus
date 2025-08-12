<?php

namespace App\Http\Middleware;

use App\Domain\Enums\MessageStatus;
use App\Models\Message;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarkMessagesAfterResponse
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        Message::markMessages($request->attributes->get('_messageIds', []),
            MessageStatus::Success->value, null);
    }
}
