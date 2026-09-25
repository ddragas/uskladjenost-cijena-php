<?php

declare(strict_types=1);

namespace UskladjenostCijena\Resources;

/** What to print next to a price. Scope compliance:read. */
final class Compliance extends Resource
{
    /** The decision for one offer, now or at a moment. @return array<string, mixed> */
    public function forOffer(string $offerId, ?string $at = null, ?string $locale = null): array
    {
        return $this->client->request('GET', 'api/v1/offers/'.rawurlencode($offerId).'/compliance', ['query' => self::clean(['at' => $at, 'locale' => $locale])]);
    }

    /**
     * The decision by your own product id. `scope` may carry merchant_id,
     * source_system, location_code, channel_code, at, locale.
     *
     * @param  array<string, mixed>  $scope
     * @return array<string, mixed>
     */
    public function byExternal(string $externalId, array $scope = []): array
    {
        return $this->client->request('GET', 'api/v1/compliance/by-external/'.rawurlencode($externalId), ['query' => self::clean($scope)]);
    }

    /** Decisions for up to 500 offers (a page, a basket). @param list<string> $offerIds @return array<string, mixed> */
    public function query(array $offerIds, ?string $at = null, ?string $locale = null): array
    {
        return $this->client->request('POST', 'api/v1/compliance/query', ['json' => self::clean(['offer_ids' => array_values($offerIds), 'at' => $at, 'locale' => $locale])]);
    }

    /** Every active offer's decision, one page. Filters: merchant_id, location_id, sales_channel_id, kind, needs_review, updated_since, cursor, limit. @param array<string, mixed> $filters @return array<string, mixed> */
    public function list(array $filters = []): array
    {
        return $this->client->request('GET', 'api/v1/compliance', ['query' => self::clean($filters)]);
    }

    /** @param array<string, mixed> $filters @return \Generator<int, array<string, mixed>> */
    public function all(array $filters = []): \Generator
    {
        $cursor = null;
        do {
            $page = $this->list($filters + ['cursor' => $cursor]);
            foreach ($page['data'] ?? [] as $row) {
                yield $row;
            }
            $cursor = $page['next_cursor'] ?? null;
        } while ($cursor !== null);
    }
}
