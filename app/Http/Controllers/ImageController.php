<?php

namespace App\Http\Controllers;

use App\Domain\Services\ImageFetchService;
use App\Domain\Services\ServiceException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImageController extends ApiController
{
    /**
     * Fetch an image from the URL supplied in the request body and return its raw bytes.
     */
    public function fetch(Request $request, ImageFetchService $imageFetchService): Response|JsonResponse
    {
        try {
            $validated = $request->validate([
                'URL' => ['required', 'string', 'max:2048'],
            ]);

            $image = $imageFetchService->fetch($validated['URL']);

            return response($image['contents'], 200, [
                'Content-Type' => $image['contentType'],
                'Content-Length' => (string) strlen($image['contents']),
                'Content-Disposition' => 'inline',
                'Cache-Control' => 'private, max-age=300',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'result' => null,
                'code' => 422,
                'description' => $e->getMessage(),
                'errorMessages' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            // Errors keep the standard JSON envelope used by the rest of the API,
            // while the HTTP status mirrors the mapped error code.
            $response = $this->send($e);
            $code = $e instanceof ServiceException ? $e->getCode() : 0;

            return $code >= 400 && $code < 600
                ? $response->setStatusCode($code)
                : $response;
        }
    }
}
