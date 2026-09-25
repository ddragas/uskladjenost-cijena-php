<?php

declare(strict_types=1);

namespace UskladjenostCijena\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use UskladjenostCijena\ApiException;
use UskladjenostCijena\Client;

/** Every resource sends the documented request and returns the decoded body; every refusal is an ApiException. */
final class ClientTest extends TestCase
{
    /** @var list<RequestInterface> */
    private array $sent = [];

    private MockHandler $mock;

    private Client $api;

    protected function setUp(): void
    {
        $this->mock = new MockHandler;
        $stack = HandlerStack::create($this->mock);
        $stack->push(Middleware::history($this->sent));
        $this->api = new Client('pc_test_abc', 'https://example.test', ['handler' => $stack]);
    }

    private function answer(int $status, array $body): void
    {
        $this->mock->append(new Response($status, ['Content-Type' => 'application/json'], (string) json_encode($body)));
    }

    private function last(): RequestInterface
    {
        return $this->sent[count($this->sent) - 1]['request'];
    }

    public function test_the_token_and_the_user_agent_travel_with_every_call(): void
    {
        $this->answer(200, ['data' => ['pong' => true, 'tenant' => 'Firma']]);
        $this->assertSame('Firma', $this->api->ping()['data']['tenant']);
        $this->assertSame('Bearer pc_test_abc', $this->last()->getHeaderLine('Authorization'));
        $this->assertStringStartsWith('uskladjenost-cijena-php/', $this->last()->getHeaderLine('User-Agent'));
        $this->assertSame('https://example.test/api/v1/ping', (string) $this->last()->getUri());
    }

    public function test_items_are_written_and_read_by_the_callers_ids(): void
    {
        $this->answer(201, ['data' => ['id' => 'i1', 'offer_id' => 'o1']]);
        $this->assertSame('o1', $this->api->items()->upsert('SKU/1', ['merchant_id' => 'm1', 'kind' => 'product', 'name' => 'Deterdžent'])['data']['offer_id']);
        $this->assertSame('PUT', $this->last()->getMethod());
        $this->assertSame('/api/v1/items/SKU%2F1', $this->last()->getUri()->getPath());
        $this->assertSame('Deterdžent', json_decode((string) $this->last()->getBody(), true)['name']);

        $this->answer(200, ['data' => [['id' => 'i1']], 'next_cursor' => 'i1']);
        $this->answer(200, ['data' => [['id' => 'i2']], 'next_cursor' => null]);
        $ids = array_column(iterator_to_array($this->api->items()->all(['merchant_id' => 'm1', 'limit' => 1]), false), 'id');
        $this->assertSame(['i1', 'i2'], $ids);
        parse_str($this->last()->getUri()->getQuery(), $query);
        $this->assertSame(['merchant_id' => 'm1', 'limit' => '1', 'cursor' => 'i1'], $query);

        $this->answer(200, ['data' => ['id' => 'i1']]);
        $this->api->items()->get('SKU-1', 'm1');
        $this->assertSame('merchant_id=m1', $this->last()->getUri()->getQuery());

        $this->answer(200, ['data' => [], 'summary' => ['created' => 2, 'updated' => 0, 'errors' => 0]]);
        $this->assertSame(2, $this->api->items()->bulk([['external_id' => 'a', 'name' => 'A'], ['external_id' => 'b', 'name' => 'B']])['summary']['created']);
    }

    public function test_prices_go_in_by_offer_or_by_the_callers_id_with_an_idempotency_key(): void
    {
        $this->answer(201, ['data' => ['id' => 7, 'regular_price' => '12.50']]);
        $this->api->prices()->record('o1', ['regular_price_minor' => 1250], 'k-1');
        $this->assertSame('k-1', $this->last()->getHeaderLine('Idempotency-Key'));
        $this->assertSame('/api/v1/offers/o1/price-events', $this->last()->getUri()->getPath());

        $this->answer(201, ['data' => ['id' => 8, 'scope' => ['location_code' => 'PU-01', 'exact' => true]]]);
        $result = $this->api->prices()->recordByExternal('SKU-1', ['regular_price_minor' => 1250, 'effective_price_minor' => 990], ['location_code' => 'PU-01', 'merchant_id' => null]);
        $this->assertSame('PU-01', $result['data']['scope']['location_code']);
        $this->assertSame(['regular_price_minor' => 1250, 'effective_price_minor' => 990, 'location_code' => 'PU-01'], json_decode((string) $this->last()->getBody(), true));
        $this->assertFalse($this->last()->hasHeader('Idempotency-Key'));

        $this->answer(200, ['data' => [['id' => 8], ['id' => 7]], 'next_cursor' => null]);
        $this->assertCount(2, $this->api->prices()->history('o1', '2026-09-01T00:00:00Z', null, 50)['data']);
        $this->assertSame('since=2026-09-01T00%3A00%3A00Z&limit=50', $this->last()->getUri()->getQuery());

        $this->answer(200, ['data' => [], 'summary' => ['created' => 1, 'duplicates' => 0, 'errors' => 0]]);
        $this->api->prices()->bulk([['offer_id' => 'o1', 'regular_price_minor' => 100]]);
        $this->assertSame('/api/v1/price-events/bulk', $this->last()->getUri()->getPath());
    }

    public function test_compliance_publications_imports_and_webhooks(): void
    {
        $this->answer(200, ['data' => ['anchor_display' => ['label' => 'Cijena na dan 10. 9. 2026.: 22,00 €']]]);
        $this->assertStringContainsString('22,00', $this->api->compliance()->byExternal('SKU-1', ['location_code' => 'PU-01', 'locale' => 'hr'])['data']['anchor_display']['label']);
        $this->assertSame('/api/v1/compliance/by-external/SKU-1', $this->last()->getUri()->getPath());
        $this->assertSame('location_code=PU-01&locale=hr', $this->last()->getUri()->getQuery());

        $this->answer(200, ['data' => []]);
        $this->api->compliance()->query(['o1', 'o2'], null, 'en');
        $this->assertSame(['offer_ids' => ['o1', 'o2'], 'locale' => 'en'], json_decode((string) $this->last()->getBody(), true));

        $this->answer(200, ['data' => [['scope_id' => 's1', 'stale' => false]]]);
        $this->assertFalse($this->api->publications()->scopes()['data'][0]['stale']);
        $this->answer(202, []);
        $this->assertSame(202, $this->api->publications()->publish('s1')['_status']);

        $this->answer(202, ['data' => ['id' => 5, 'status' => 'checked', 'preview' => ['new' => 3]]]);
        $import = $this->api->imports()->upload('m1', 'items_prices', "sifra;naziv;cijena\nA;Artikl;1,00\n", 'cjenik.csv', ['dry_run' => true, 'on_conflict' => 'update']);
        $this->assertSame(3, $import['data']['preview']['new']);
        $this->assertStringContainsString('multipart/form-data', $this->last()->getHeaderLine('Content-Type'));
        $multipart = (string) $this->last()->getBody();
        $this->assertStringContainsString('name="dry_run"', $multipart);
        $this->assertStringContainsString("\r\n1\r\n", $multipart);
        $this->assertStringContainsString('filename="cjenik.csv"', $multipart);

        $this->answer(201, ['data' => ['id' => 'w1', 'secret' => 'whsec_x']]);
        $this->assertSame('whsec_x', $this->api->webhooks()->create('https://shop.test/hook', ['publication.succeeded'])['data']['secret']);
        $this->answer(204, []);
        $this->api->webhooks()->delete('w1');
        $this->assertSame('DELETE', $this->last()->getMethod());
    }

    public function test_a_refusal_becomes_an_exception_with_the_apis_code_and_details(): void
    {
        $this->answer(422, ['error' => ['code' => 'validation', 'message' => 'The item could not be saved.', 'details' => ['name' => ['Missing.']]]]);
        try {
            $this->api->items()->upsert('x', []);
            $this->fail('no exception');
        } catch (ApiException $e) {
            $this->assertSame('validation', $e->errorCode);
            $this->assertSame(422, $e->status);
            $this->assertSame(['Missing.'], $e->details['name']);
        }

        $this->answer(403, ['error' => ['code' => 'insufficient_scope', 'message' => 'This token lacks the prices:write scope.']]);
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('prices:write');
        $this->api->prices()->record('o1', ['regular_price_minor' => 1]);
    }
}
