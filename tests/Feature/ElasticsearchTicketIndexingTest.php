<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\TicketCreated;
use App\Listeners\IndexTicketInElasticsearch;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ElasticsearchTicketIndexingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_created_event_indexes_a_search_document_without_customer_personal_data(): void
    {
        config(['app.env' => 'local', 'services.elasticsearch.url' => 'http://elasticsearch:9200']);
        Http::fake();

        $customer = User::factory()->create();
        $ticket = Ticket::create([
            'subject' => 'Cannot sign in',
            'description' => 'Password reset is not working.',
            'status' => 'open',
            'priority' => 'high',
            'user_id' => $customer->id,
        ]);

        (new IndexTicketInElasticsearch())->handle(new TicketCreated($ticket));

        Http::assertSent(function (Request $request) use ($ticket): bool {
            return $request->url() === 'http://elasticsearch:9200/helpdesk-tickets/_doc/' . $ticket->id
                && $request['subject'] === 'Cannot sign in'
                && $request['priority'] === 'high'
                && ! array_key_exists('customer_email', $request->data());
        });
    }
}
