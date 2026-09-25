<?php

declare(strict_types=1);

namespace UskladjenostCijena\Resources;

/** Price events: append-only, idempotent. Scopes prices:write / prices:read. */
final class Prices extends Resource
{
    /**
     * Record a price on an offer. Pass an idempotency key so a retry never doubles the history.
     *
     * @param  array<string, mixed>  $event  regular_price_minor, effective_price_minor?, price_from?, price_to_minor?, valid_from?, ...
     * @return array<string, mixed>
     */
    public function record(string $offerId, array $event, ?string $idempotencyKey = null): array
    {
        return $this->client->request('POST', 'api/v1/offers/'.rawurlencode($offerId).'/price-events', self::clean(['json' => $event, 'headers' => $idempotencyKey === null ? null : ['Idempotency-Key' => $idempotencyKey]]));
    }

    /**
     * Record a price by your own product id; no offer id needed. `scope` may carry
     * merchant_id, source_system, location_code, channel_code.
     *
     * @param  array<string, mixed>  $event
     * @param  array<string, mixed>  $scope
     * @return array<string, mixed>
     */
    public function recordByExternal(string $externalId, array $event, array $scope = [], ?string $idempotencyKey = null): array
    {
        return $this->client->request('POST', 'api/v1/prices/by-external/'.rawurlencode($externalId), self::clean(['json' => $event + self::clean($scope), 'headers' => $idempotencyKey === null ? null : ['Idempotency-Key' => $idempotencyKey]]));
    }

    /** Up to 1000 rows, each an event plus `offer_id` (and optionally `idempotency_key`). @param list<array<string, mixed>> $events @return array<string, mixed> */
    public function bulk(array $events): array
    {
        return $this->client->request('POST', 'api/v1/price-events/bulk', ['json' => ['events' => array_values($events)]]);
    }

    /** The offer's history, newest first. @return array<string, mixed> with `data` and `next_cursor` */
    public function history(string $offerId, ?string $since = null, ?int $cursor = null, ?int $limit = null): array
    {
        return $this->client->request('GET', 'api/v1/offers/'.rawurlencode($offerId).'/price-events', ['query' => self::clean(['since' => $since, 'cursor' => $cursor, 'limit' => $limit])]);
    }
}
