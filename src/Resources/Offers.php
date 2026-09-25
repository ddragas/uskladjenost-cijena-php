<?php

declare(strict_types=1);

namespace UskladjenostCijena\Resources;

/** One offer: the item, the scope, the price in force. Scope prices:read. */
final class Offers extends Resource
{
    /** @return array<string, mixed> */
    public function get(string $offerId): array
    {
        return $this->client->request('GET', 'api/v1/offers/'.rawurlencode($offerId));
    }
}
