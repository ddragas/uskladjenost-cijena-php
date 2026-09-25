<?php

declare(strict_types=1);

namespace UskladjenostCijena\Resources;

/** Webhook endpoints. Scope webhooks:manage. Verify deliveries with Webhooks\Signature. */
final class Webhooks extends Resource
{
    /** @return array<string, mixed> */
    public function list(): array
    {
        return $this->client->request('GET', 'api/v1/webhooks');
    }

    /** Add an endpoint; the answer carries the `secret` once. @param list<string> $events @return array<string, mixed> */
    public function create(string $url, array $events, ?string $description = null): array
    {
        return $this->client->request('POST', 'api/v1/webhooks', ['json' => self::clean(['url' => $url, 'events' => array_values($events), 'description' => $description])]);
    }

    /** @return array<string, mixed> */
    public function test(string $endpointId): array
    {
        return $this->client->request('POST', 'api/v1/webhooks/'.rawurlencode($endpointId).'/test');
    }

    /** @return array<string, mixed> */
    public function delete(string $endpointId): array
    {
        return $this->client->request('DELETE', 'api/v1/webhooks/'.rawurlencode($endpointId));
    }
}
