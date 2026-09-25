<?php

declare(strict_types=1);

namespace UskladjenostCijena\Resources;

use UskladjenostCijena\Client;

abstract class Resource
{
    public function __construct(protected readonly Client $client) {}

    /** Drops nulls so an omitted option is not sent as null. @param array<string, mixed> $params @return array<string, mixed> */
    protected static function clean(array $params): array
    {
        return array_filter($params, fn ($v) => $v !== null);
    }
}
