<?php

namespace App\Http\Middleware;

use App\Domain\Enums\MessageCode;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ParseHeaders
{
    private static array $parsedHeaders = [];

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sender = $request->headers->get('Sender');
        $recipient = $request->headers->get('Recipient');
        $messageCode = $request->headers->get('MessageCode');
        $messageId = $request->headers->get('MessageID');

        /** @var User|null $user */
        $user = $request->user();
        $isPost = $request->isMethod('post');

        if ($user === null) {
            return new JsonResponse(['result' => null, 'errorMessage' => 'User not found.'], 401);
        }

        if ($isPost) {
            if ($sender && ($sender !== $user->code) && !$user->is_master) {
                return new JsonResponse(['result' => null, 'errorMessage' => 'Header Sender is wrong to the current user.'], 400);
            }
        } else {
            if ($recipient && ($recipient !== $user->code)) {
                return new JsonResponse(['result' => null, 'errorMessage' => 'Header Recipient is wrong to the current user.'], 400);
            }
        }

        if ($messageCode && !in_array($messageCode, MessageCode::names(), true)) {
            return new JsonResponse(['result' => null, 'errorMessage' => 'Header MessageCode is wrong.'], 400);
        }

        static::$parsedHeaders = compact('sender', 'recipient', 'messageCode', 'messageId');

        return $next($request);
    }

    public function getHeaders(): array
    {
        return static::$parsedHeaders;
    }
}
