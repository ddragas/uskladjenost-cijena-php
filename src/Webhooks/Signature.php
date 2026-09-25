<?php

declare(strict_types=1);

namespace UskladjenostCijena\Webhooks;

/**
 * Verifies a delivery: `X-PC-Signature: v1=HMAC-SHA256(secret, timestamp + "." + body)`
 * with `X-PC-Timestamp`; deliveries older than the tolerance are refused.
 */
final class Signature
{
    public static function sign(string $secret, string $timestamp, string $body): string
    {
        return 'v1='.hash_hmac('sha256', $timestamp.'.'.$body, $secret);
    }

    public static function verify(string $secret, string $signatureHeader, string $timestampHeader, string $body, int $toleranceSeconds = 300, ?int $now = null): bool
    {
        if (! ctype_digit($timestampHeader)) {
            return false;
        }
        if (abs(($now ?? time()) - (int) $timestampHeader) > $toleranceSeconds) {
            return false;
        }
        foreach (explode(',', $signatureHeader) as $candidate) {
            if (hash_equals(self::sign($secret, $timestampHeader, $body), trim($candidate))) {
                return true;
            }
        }

        return false;
    }

    /**
     * The verified event from a PSR-7-less request: headers as an array, body as a string.
     *
     * @param  array<string, string>  $headers  case-insensitive
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException when the signature does not hold
     */
    public static function event(string $secret, array $headers, string $body, int $toleranceSeconds = 300): array
    {
        $lower = array_change_key_case($headers, CASE_LOWER);
        if (! self::verify($secret, (string) ($lower['x-pc-signature'] ?? ''), (string) ($lower['x-pc-timestamp'] ?? ''), $body, $toleranceSeconds)) {
            throw new \InvalidArgumentException('The webhook signature does not hold.');
        }
        $data = json_decode($body, true);

        return is_array($data) ? $data + ['event' => $lower['x-pc-event'] ?? null, 'event_id' => $lower['x-pc-event-id'] ?? null] : [];
    }
}
