<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\TicketSearchService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TicketSearchServiceTest extends TestCase
{
    public function test_it_returns_ticket_ids_from_elasticsearch(): void
    {
        config(['services.elasticsearch.enabled' => true, 'services.elasticsearch.url' => 'http://elasticsearch:9200']);
        Http::fake([
            'elasticsearch:9200/helpdesk-tickets/_search*' => Http::response([
                'hits' => [
                    'hits' => [
                        ['_id' => '12'],
                        ['_id' => '24'],
                    ],
                ],
            ]),
        ]);

        $ids = app(TicketSearchService::class)->searchIds('printer offline');

        $this->assertSame([12, 24], $ids);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/helpdesk-tickets/_search'));
    }

    public function test_it_returns_null_when_elasticsearch_is_unavailable_to_enable_sql_fallback(): void
    {
        config(['services.elasticsearch.url' => 'http://elasticsearch:9200']);
        Http::fake([
            'elasticsearch:9200/*' => Http::response(['error' => 'unavailable'], 503),
        ]);

        $this->assertNull(app(TicketSearchService::class)->searchIds('printer offline'));
    }
}
