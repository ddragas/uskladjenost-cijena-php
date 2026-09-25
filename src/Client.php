<?php

declare(strict_types=1);

namespace UskladjenostCijena;

use GuzzleHttp\Client as Http;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use UskladjenostCijena\Resources\Compliance;
use UskladjenostCijena\Resources\Imports;
use UskladjenostCijena\Resources\Items;
use UskladjenostCijena\Resources\Merchants;
use UskladjenostCijena\Resources\Offers;
use UskladjenostCijena\Resources\Prices;
use UskladjenostCijena\Resources\Publications;
use UskladjenostCijena\Resources\Webhooks;

/**
 * The Usklađenost cijena API from PHP.
 *
 *   $api = new Client('pc_live_...');
 *   $item = $api->items()->upsert('SKU-1', ['merchant_id' => $m, 'kind' => 'product', 'name' => 'Deterdžent']);
 *   $api->prices()->recordByExternal('SKU-1', ['regular_price_minor' => 1250]);
 *   $decision = $api->compliance()->byExternal('SKU-1');
 *   echo $decision['anchor_display']['label'];
 *
 * Every method returns the decoded JSON body (an array) plus `_status`, the HTTP
 * status; every refusal is an
 * ApiException with the API's error code, the HTTP status and the details.
 */
final class Client
{
    public const VERSION = '1.0.0';

    private Http $http;

    /** @param array<string, mixed> $guzzleOptions extra options for the underlying Guzzle client (proxy, timeout, handler for tests) */
    public function __construct(string $token, string $baseUrl = 'https://uskladjenost-cijena.com', array $guzzleOptions = [])
    {
        $this->http = new Http($guzzleOptions + [
            'base_uri' => rtrim($baseUrl, '/').'/',
            'timeout' => 30,
            'http_errors' => true,
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
                'User-Agent' => 'uskladjenost-cijena-php/'.self::VERSION,
            ],
        ]);
    }

    /** @return array<string, mixed> */
    public function ping(): array
    {
        return $this->request('GET', 'api/v1/ping');
    }

    public function merchants(): Merchants
    {
        return new Merchants($this);
    }

    public function items(): Items
    {
        return new Items($this);
    }

    public function prices(): Prices
    {
        return new Prices($this);
    }

    public function offers(): Offers
    {
        return new Offers($this);
    }

    public function compliance(): Compliance
    {
        return new Compliance($this);
    }

    public function publications(): Publications
    {
        return new Publications($this);
    }

    public function imports(): Imports
    {
        return new Imports($this);
    }

    public function webhooks(): Webhooks
    {
        return new Webhooks($this);
    }

    /**
     * One call. `$options` are Guzzle options (json, query, multipart, headers).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    public function request(string $method, string $path, array $options = []): array
    {
        try {
            $response = $this->http->request($method, ltrim($path, '/'), $options);
        } catch (BadResponseException $e) {
            throw self::refusal($e->getResponse());
        } catch (GuzzleException $e) {
            throw new ApiException('transport', $e->getMessage(), 0);
        }

        return self::decode($response);
    }

    /** @return array<string, mixed> */
    private static function decode(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        if ($body === '') {
            return ['_status' => $response->getStatusCode()];
        }
        $data = json_decode($body, true);
        if (! is_array($data)) {
            throw new ApiException('malformed', 'The API answered with something that is not JSON.', $response->getStatusCode());
        }
        $data['_status'] = $response->getStatusCode();

        return $data;
    }

    private static function refusal(ResponseInterface $response): ApiException
    {
        $status = $response->getStatusCode();
        $data = json_decode((string) $response->getBody(), true);
        $error = is_array($data) && isset($data['error']) && is_array($data['error']) ? $data['error'] : [];
        $code = (string) ($error['code'] ?? match (true) {
            $status === 401 => 'unauthenticated',
            $status === 403 => 'forbidden',
            $status === 404 => 'not_found',
            $status === 429 => 'rate_limited',
            default => 'http_'.$status,
        });
        $message = (string) ($error['message'] ?? ($data['message'] ?? 'HTTP '.$status));
        $details = is_array($error['details'] ?? null) ? $error['details'] : (is_array($data['errors'] ?? null) ? $data['errors'] : []);

        return new ApiException($code, $message, $status, $details);
    }
}
