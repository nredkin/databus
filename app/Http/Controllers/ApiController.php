<?php

namespace App\Http\Controllers;

use App\Domain\Services\ServiceException;
use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;
use \PDOException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

abstract class ApiController extends Controller
{
    /**
     * @return User
     * @throws HttpException|NotFoundHttpException
     */
    protected function user(): User
    {
        /** @var User|null $user */
        $user = auth()->user();
        abort_if(!$user, 401);

        return $user;
    }

    protected function send($payload = null, $meta = [], $headers = []): JsonResponse
    {
        $statusCode = 200;

        if (is_callable($payload)) {
            return $this->withErrorControl($payload);
        }

        if ($payload instanceof Throwable) {
            if ($payload instanceof UnauthorizedException) {
                $jsonResponse = [
                    'result' => null,
                    'code' => 401,
                    'description' => 'Unauthorized access.',
                ];
                $statusCode = 401;
            } elseif ($payload instanceof AuthorizationException) {
                $jsonResponse = [
                    'result' => null,
                    'code' => 403,
                    'description' => 'Unauthorized access.',
                ];
                $statusCode = 403;
            } elseif ($payload instanceof PDOException) {
                $jsonResponse = [
                    'result' => null,
                    'code' => $payload->getCode(),
                    'description' => 'Database error [' . $payload->getCode() . ']',
                    'errorMessage' => 'Вероятно, есть ошибка в сохраняемых данных.',
                ];
            } elseif ($payload instanceof HttpExceptionInterface) {
                $jsonResponse = [
                    'result' => null,
                    'code' => (int)($payload->getStatusCode() ?: 500),
                    'description' => $payload->getMessage(),
                ];
            } elseif ($payload instanceof ServiceException) {
                $jsonResponse = [
                    'result' => null,
                    'code' => (int)($payload->getCode() ?: 400),
                    'description' => 'Service exception.',
                    'errorMessage' => $payload->getMessage(),
                ];
            } else {
                $jsonResponse = [
                    'result' => null,
                    'code' => (int)($payload->getCode() ?: 500),
                    'description' => $payload->getMessage(),
                ];
            }

            if (env('APP_DEBUG')) {
                $jsonResponse['_meta'] = $payload->getMessage();
            }

            Log::error('App Error', [
                'msg' => $payload->getMessage(),
                'code' => $payload->getCode(),
                'request' => request()?->all() ?: 'No request data.',
            ]);
        } else {
            $jsonResponse = [
                'code' => 0,
                'description' => 'OK',
                'result' => $payload,
            ];
            if ($meta && config('app.debug')) {
                $jsonResponse['_meta'] = $meta;
            }
        }

        return response()
            ->json($jsonResponse)
            ->setStatusCode($statusCode)
            ->withHeaders($headers);
    }

    protected function withErrorControl(Closure $func): JsonResponse
    {
        try {
            return $this->send($func());
        } catch (Throwable $e) {
            return $this->send($e);
        }
    }
}
