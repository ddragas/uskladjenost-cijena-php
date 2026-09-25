<?php

declare(strict_types=1);

namespace UskladjenostCijena\Resources;

/** Merchants, their locations and sales channels. Scopes catalog:read / catalog:write. */
final class Merchants extends Resource
{
    /** @return array<string, mixed> */
    public function list(): array
    {
        return $this->client->request('GET', 'api/v1/merchants');
    }

    /** Create or update a merchant by your own key. @param array<string, mixed> $data @return array<string, mixed> */
    public function upsert(string $externalKey, array $data): array
    {
        return $this->client->request('PUT', 'api/v1/merchants/'.rawurlencode($externalKey), ['json' => $data]);
    }

    /** @return array<string, mixed> */
    public function locations(string $merchantId): array
    {
        return $this->client->request('GET', 'api/v1/merchants/'.rawurlencode($merchantId).'/locations');
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function upsertLocation(string $merchantId, string $code, array $data): array
    {
        return $this->client->request('PUT', 'api/v1/merchants/'.rawurlencode($merchantId).'/locations/'.rawurlencode($code), ['json' => $data]);
    }

    /** @return array<string, mixed> */
    public function channels(string $merchantId): array
    {
        return $this->client->request('GET', 'api/v1/merchants/'.rawurlencode($merchantId).'/channels');
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function upsertChannel(string $merchantId, string $code, array $data): array
    {
        return $this->client->request('PUT', 'api/v1/merchants/'.rawurlencode($merchantId).'/channels/'.rawurlencode($code), ['json' => $data]);
    }
}
