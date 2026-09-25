<?php

declare(strict_types=1);

namespace UskladjenostCijena\Resources;

/** The public price lists: one scope per location and public webshop. Scopes publications:read / publications:manage. */
final class Publications extends Resource
{
    /** @return array<string, mixed> */
    public function scopes(): array
    {
        return $this->client->request('GET', 'api/v1/publication-scopes');
    }

    /** @return array<string, mixed> */
    public function status(string $scopeId): array
    {
        return $this->client->request('GET', 'api/v1/publication-scopes/'.rawurlencode($scopeId).'/status');
    }

    /** Publish now (queued). @return array<string, mixed> */
    public function publish(string $scopeId): array
    {
        return $this->client->request('POST', 'api/v1/publication-scopes/'.rawurlencode($scopeId).'/publish');
    }
}
