<?php

namespace App\Domain\Services;

use GuzzleHttp\Psr7\LimitStream;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Downloads a remote image and returns its raw bytes.
 *
 * The fetcher is deliberately restrictive: only http/https URLs are accepted,
 * every hop (including redirects) must resolve to a public IP address, and
 * both the transfer time and the amount of received data are capped.
 */
class ImageFetchService
{
    public function __construct(
        private readonly HostResolver $resolver = new HostResolver,
        private readonly IpGuard $ipGuard = new IpGuard,
    ) {}

    /**
     * Fetch the image located at the given URL.
     *
     * @return array{contents: string, contentType: string}
     *
     * @throws ServiceException
     */
    public function fetch(string $url): array
    {
        try {
            $parts = parse_url($url);
            $host = $parts['host'] ?? null;
            $scheme = strtolower($parts['scheme'] ?? '');
            $port = $this->resolvePort($parts, $scheme);

            if (empty($host) || empty($scheme)) {
                return $this->fail(400, "Malformed URL: [{$url}].");
            }

            if (! in_array($scheme, config('images.allowed_schemes', ['http', 'https']), true)) {
                return $this->fail(400, "URL scheme [{$scheme}] is not allowed.");
            }

            $address = $this->resolvePublicAddress($host);

            $response = $this->request($url, $host, $port, $address);
            $status = $response->getStatusCode();

            if ($status < 200 || $status >= 300) {
                return $this->fail(502, "Upstream responded with status [{$status}].");
            }

            $contents = $this->readLimited($response);
            $contentType = $this->normalizeContentType($response->getHeaderLine('Content-Type'));

            if ($contentType === null) {
                return $this->fail(415, 'Upstream response is not an image.');
            }

            return [
                'contents' => $contents,
                'contentType' => $contentType,
            ];
        } catch (ServiceException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            throw new ServiceException('Unable to reach the image source: '.$e->getMessage(), 504, $e);
        } catch (Throwable $e) {
            Log::error('Image fetch failed', ['url' => $url, 'error' => $e->getMessage()]);

            throw new ServiceException('Unable to fetch the image: '.$e->getMessage(), 502, $e);
        }
    }

    /**
     * Validate the resolved address and pin the connection to it.
     *
     * Pinning via CURLOPT_RESOLVE closes the DNS rebinding window between the
     * validation lookup and the connection actually made by cURL.
     */
    private function request(string $url, string $host, int $port, string $address): ResponseInterface
    {
        /** @var PendingRequest $request */
        $request = Http::withOptions([
            'stream' => true,
            'connect_timeout' => config('images.connect_timeout'),
            'timeout' => config('images.timeout'),
            'http_errors' => false,
            'curl' => [
                CURLOPT_RESOLVE => ["{$host}:{$port}:{$address}"],
            ],
            'allow_redirects' => [
                'max' => config('images.max_redirects'),
                'strict' => true,
                'referer' => false,
                'protocols' => config('images.allowed_schemes', ['http', 'https']),
                'track_redirects' => false,
                'on_redirect' => function ($request, $response, $uri) {
                    $this->assertPublicHost($uri->getHost());
                },
            ],
        ]);

        return $request->get($url)->toPsrResponse();
    }

    /**
     * Abort the transfer as soon as more than max_bytes have been received.
     */
    private function readLimited(ResponseInterface $response): string
    {
        $maxBytes = (int) config('images.max_bytes', 10 * 1024 * 1024);
        $declared = $response->getHeaderLine('Content-Length');

        if ($declared !== '' && (int) $declared > $maxBytes) {
            return $this->fail(413, 'Image is larger than the allowed limit.');
        }

        $body = new LimitStream(Utils::streamFor($response->getBody()), $maxBytes + 1);

        $contents = '';
        while (! $body->eof()) {
            $chunk = $body->read(8192);

            if ($chunk === '') {
                break;
            }

            $contents .= $chunk;

            if (strlen($contents) > $maxBytes) {
                return $this->fail(413, 'Image is larger than the allowed limit.');
            }
        }

        return $contents;
    }

    /**
     * Resolve the host and make sure every address is publicly routable.
     *
     * @throws ServiceException
     */
    private function assertPublicHost(?string $host): void
    {
        if (empty($host)) {
            $this->fail(400, 'Malformed URL: host is missing.');
        }

        $this->resolvePublicAddress($host);
    }

    /**
     * @throws ServiceException
     */
    private function resolvePublicAddress(string $host): string
    {
        $addresses = $this->resolver->resolve($host);

        if (empty($addresses)) {
            return $this->fail(502, "Unable to resolve host [{$host}].");
        }

        foreach ($addresses as $address) {
            if (! $this->ipGuard->isPublic($address)) {
                return $this->fail(403, "Host [{$host}] resolves to a non-public address.");
            }
        }

        return $addresses[0];
    }

    private function normalizeContentType(string $contentType): ?string
    {
        $contentType = trim(strtolower(strtok($contentType, ';') ?: ''));

        if ($contentType === '' || ! str_starts_with($contentType, config('images.content_type_prefix', 'image/'))) {
            return null;
        }

        return $contentType;
    }

    /**
     * @param  array<string, mixed>  $parts
     */
    private function resolvePort(array $parts, string $scheme): int
    {
        if (isset($parts['port'])) {
            return (int) $parts['port'];
        }

        return $scheme === 'https' ? 443 : 80;
    }

    /**
     * @throws ServiceException
     */
    private function fail(int $status, string $message): never
    {
        Log::warning('Image fetch rejected', ['status' => $status, 'error' => $message]);

        throw new ServiceException($message, $status);
    }
}
