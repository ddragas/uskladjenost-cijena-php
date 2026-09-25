<?php

declare(strict_types=1);

namespace UskladjenostCijena\Tests;

use PHPUnit\Framework\TestCase;
use UskladjenostCijena\Webhooks\Signature;

final class SignatureTest extends TestCase
{
    public function test_a_fresh_delivery_verifies_and_a_stale_or_forged_one_does_not(): void
    {
        $body = '{"event":"publication.succeeded","data":{"scope_id":"s1"}}';
        $ts = (string) time();
        $sig = Signature::sign('whsec_x', $ts, $body);
        $this->assertTrue(Signature::verify('whsec_x', $sig, $ts, $body));
        $this->assertTrue(Signature::verify('whsec_x', 'v1=deadbeef,'.$sig, $ts, $body), 'a rotated secret may send two signatures');
        $this->assertFalse(Signature::verify('whsec_y', $sig, $ts, $body));
        $this->assertFalse(Signature::verify('whsec_x', $sig, $ts, $body.' '));
        $this->assertFalse(Signature::verify('whsec_x', $sig, (string) (time() - 301), $body));
        $this->assertFalse(Signature::verify('whsec_x', $sig, 'abc', $body));

        $event = Signature::event('whsec_x', ['X-PC-Signature' => $sig, 'X-PC-Timestamp' => $ts, 'X-PC-Event' => 'publication.succeeded', 'X-PC-Event-Id' => 'e1'], $body);
        $this->assertSame('s1', $event['data']['scope_id']);
        $this->assertSame('e1', $event['event_id']);

        $this->expectException(\InvalidArgumentException::class);
        Signature::event('whsec_x', ['X-PC-Signature' => 'v1=nope', 'X-PC-Timestamp' => $ts], $body);
    }
}
