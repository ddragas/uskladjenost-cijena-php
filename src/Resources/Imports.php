<?php

declare(strict_types=1);

namespace UskladjenostCijena\Resources;

/** CSV / XLSX uploads with a dry run. Scope imports:write. */
final class Imports extends Resource
{
    /**
     * Upload a file. `type`: items_prices, historical_prices, anchors, locations, availability.
     * `options`: on_conflict (skip|update), dry_run (bool). The answer carries counts, per-row
     * errors and `preview` (new / changed / unchanged / missing).
     *
     * @param  resource|string  $file  a stream or the file's contents
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function upload(string $merchantId, string $type, mixed $file, string $filename, array $options = []): array
    {
        $parts = [
            ['name' => 'merchant_id', 'contents' => $merchantId],
            ['name' => 'type', 'contents' => $type],
            ['name' => 'file', 'contents' => $file, 'filename' => $filename],
        ];
        foreach (self::clean($options) as $name => $value) {
            $parts[] = ['name' => $name, 'contents' => is_bool($value) ? ($value ? '1' : '0') : (string) $value];
        }

        return $this->client->request('POST', 'api/v1/imports', ['multipart' => $parts]);
    }

    /** @return array<string, mixed> */
    public function get(int $importId): array
    {
        return $this->client->request('GET', 'api/v1/imports/'.$importId);
    }
}
