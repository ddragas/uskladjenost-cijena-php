<?php

declare(strict_types=1);

namespace UskladjenostCijena\Resources;

/** The catalogue, by your own ids. Scopes catalog:read / catalog:write. */
final class Items extends Resource
{
    /**
     * One page of items. Filters: merchant_id, kind, active, q, updated_since, cursor, limit.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed> with `data` and `next_cursor`
     */
    public function list(array $filters = []): array
    {
        return $this->client->request('GET', 'api/v1/items', ['query' => self::clean($filters)]);
    }

    /**
     * Every item, page after page.
     *
     * @param  array<string, mixed>  $filters
     * @return \Generator<int, array<string, mixed>>
     */
    public function all(array $filters = []): \Generator
    {
        $cursor = null;
        do {
            $page = $this->list($filters + ['cursor' => $cursor]);
            foreach ($page['data'] ?? [] as $item) {
                yield $item;
            }
            $cursor = $page['next_cursor'] ?? null;
        } while ($cursor !== null);
    }

    /** @return array<string, mixed> */
    public function get(string $externalId, ?string $merchantId = null, ?string $sourceSystem = null): array
    {
        return $this->client->request('GET', 'api/v1/items/'.rawurlencode($externalId), ['query' => self::clean(['merchant_id' => $merchantId, 'source_system' => $sourceSystem])]);
    }

    /** Create or update by your own id; the answer carries `offer_id`. @param array<string, mixed> $data @return array<string, mixed> */
    public function upsert(string $externalId, array $data): array
    {
        return $this->client->request('PUT', 'api/v1/items/'.rawurlencode($externalId), ['json' => $data]);
    }

    /** Up to 500 rows, each an item plus `external_id`. @param list<array<string, mixed>> $items @return array<string, mixed> */
    public function bulk(array $items): array
    {
        return $this->client->request('POST', 'api/v1/items/bulk', ['json' => ['items' => array_values($items)]]);
    }
}
