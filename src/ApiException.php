<?php

declare(strict_types=1);

namespace UskladjenostCijena;

/**
 * The API refused or could not answer. `errorCode` is the API's own code
 * (validation, not_found, ambiguous, insufficient_scope, ...), `status`
 * the HTTP status, `details` the per-field messages of a 422.
 */
final class ApiException extends \RuntimeException
{
    /** @param array<string, mixed> $details */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status,
        public readonly array $details = [],
    ) {
        parent::__construct($message, $status);
    }
}
