# Usklađenost cijena – PHP SDK

PHP klijent za API servisa [Usklađenost cijena](https://uskladjenost-cijena.com): sidrene cijene i javni strojno čitljiv cjenik po NN 101/2026. Pokriva cijeli API: trgovce, lokacije i kanale, katalog, cijene, odluke o usklađenosti, objave cjenika, uvoz datoteka i webhookove, uz provjeru potpisa webhook isporuka.

```bash
composer require infomedia/uskladjenost-cijena-php
```

Zahtijeva PHP 8.1+ i Guzzle 7. API token (`pc_live_…` ili `pc_test_…`) izdajete u aplikaciji pod **API pristup**; token nosi opsege (scopes) i svaka metoda dolje navodi koji treba.

## Brzi početak

```php
use UskladjenostCijena\Client;

$api = new Client(getenv('PC_TOKEN'));

// 1. Artikl po vašoj šifri (catalog:write). Odgovor nosi offer_id.
$item = $api->items()->upsert('SKU-1', [
    'merchant_id' => $merchantId,
    'kind' => 'product',          // product | service | price_component
    'name' => 'Deterdžent 3 kg',
    'barcode' => '3859000000001',
    'fmcg_category' => 'cleaning', // sidro na 2. 5. 2025.
]);

// 2. Cijena po vašoj šifri, bez offer_id-a (prices:write). Iznosi u centima.
$api->prices()->recordByExternal('SKU-1', [
    'regular_price_minor' => 1250,
    'effective_price_minor' => 990,   // akcijska; nikad iznad redovne
], ['location_code' => 'PU-01'], idempotencyKey: 'order-42-line-1');

// 3. Što ispisati uz cijenu (compliance:read).
$decision = $api->compliance()->byExternal('SKU-1', ['location_code' => 'PU-01']);
echo $decision['data']['anchor_display']['label']; // "Cijena na dan 10. 9. 2026.: 12,50 €" ili null
```

## Sve metode

| Resurs | Metoda | Poziv | Opseg |
|---|---|---|---|
| — | `ping()` | `GET /api/v1/ping` | — |
| `merchants()` | `list()` | `GET /merchants` | catalog:read |
| | `upsert($key, $data)` | `PUT /merchants/{key}` | catalog:write |
| | `locations($merchantId)` / `channels($merchantId)` | `GET /merchants/{id}/locations` / `channels` | catalog:read |
| | `upsertLocation($merchantId, $code, $data)` / `upsertChannel(...)` | `PUT /merchants/{id}/locations/{code}` | catalog:write |
| `items()` | `list($filters)` / `all($filters)` | `GET /items` (kursor) | catalog:read |
| | `get($externalId, $merchantId?, $sourceSystem?)` | `GET /items/{externalId}` | catalog:read |
| | `upsert($externalId, $data)` / `bulk($items)` | `PUT /items/{id}` / `POST /items/bulk` | catalog:write |
| `prices()` | `record($offerId, $event, $key?)` | `POST /offers/{id}/price-events` | prices:write |
| | `recordByExternal($externalId, $event, $scope, $key?)` | `POST /prices/by-external/{id}` | prices:write |
| | `bulk($events)` | `POST /price-events/bulk` | prices:write |
| | `history($offerId, $since?, $cursor?, $limit?)` | `GET /offers/{id}/price-events` | prices:read |
| `offers()` | `get($offerId)` | `GET /offers/{id}` | prices:read |
| `compliance()` | `forOffer($offerId, $at?, $locale?)` | `GET /offers/{id}/compliance` | compliance:read |
| | `byExternal($externalId, $scope)` | `GET /compliance/by-external/{id}` | compliance:read |
| | `query($offerIds, $at?, $locale?)` | `POST /compliance/query` | compliance:read |
| | `list($filters)` / `all($filters)` | `GET /compliance` | compliance:read |
| `publications()` | `scopes()` / `status($scopeId)` | `GET /publication-scopes` | publications:read |
| | `publish($scopeId)` | `POST /publication-scopes/{id}/publish` | publications:manage |
| `imports()` | `upload($merchantId, $type, $file, $filename, $options)` | `POST /imports` (multipart) | imports:write |
| | `get($importId)` | `GET /imports/{id}` | imports:write |
| `webhooks()` | `list()` / `create($url, $events, $desc?)` / `test($id)` / `delete($id)` | `/webhooks` | webhooks:manage |

Svaka metoda vraća dekodirani JSON kao polje (`data`, `next_cursor`, `summary`…). Odbijanje API-ja je `UskladjenostCijena\ApiException` s `errorCode` (npr. `validation`, `not_found`, `ambiguous`, `insufficient_scope`), `status` (HTTP) i `details` (poruke po polju kod 422).

## Webhookovi

Svaka isporuka nosi `X-PC-Event`, `X-PC-Event-Id`, `X-PC-Timestamp` i `X-PC-Signature: v1=HMAC-SHA256(secret, timestamp + "." + body)`.

```php
use UskladjenostCijena\Webhooks\Signature;

$event = Signature::event($secret, getallheaders(), file_get_contents('php://input')); // baca InvalidArgumentException ako potpis ne drži
if ($event['event'] === 'publication.failed') { /* ... */ }
```

## Razvoj

```bash
composer install && vendor/bin/phpunit
```

Dokumentacija API-ja: `https://uskladjenost-cijena.com/api/docs` (referenca) i `/api/swagger` (isprobajte s tokenom). Licenca MIT, © Info Media d.o.o.

## Ostali SDK-ovi i dodaci

Ista obitelj za isti API, svaki u svom repozitoriju:

- [uskladjenost-cijena-python](https://github.com/ddragas/uskladjenost-cijena-python) – Python SDK (`uskladjenost-cijena`)
- [uskladjenost-cijena-js](https://github.com/ddragas/uskladjenost-cijena-js) – JavaScript/TypeScript SDK (`@uskladjenost-cijena/sdk`)
- [uskladjenost-cijena-woocommerce](https://github.com/ddragas/uskladjenost-cijena-woocommerce) – WooCommerce dodatak
- [uskladjenost-cijena-shopify](https://github.com/ddragas/uskladjenost-cijena-shopify) – Shopify custom app
- [uskladjenost-cijena-prestashop](https://github.com/ddragas/uskladjenost-cijena-prestashop) – PrestaShop 8 modul
