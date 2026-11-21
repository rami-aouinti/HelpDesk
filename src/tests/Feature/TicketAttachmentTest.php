<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        // These tests cover private file storage and authorization, not mail delivery.
        // The testing environment uses the synchronous queue, so fake notifications
        // to keep queued mail jobs out of the attachment test lifecycle.
        Notification::fake();

        Storage::fake('local');
    }

    public function test_customer_can_upload_and_download_an_attachment_on_their_ticket(): void
    {
        fwrite(STDERR, "STEP 1: test started\n");

        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        fwrite(STDERR, "STEP 2: customer loaded\n");

        $ticket = $this->makeTicket($customer);
        fwrite(STDERR, "STEP 3: ticket created\n");

        $file = UploadedFile::fake()->createWithContent(
            'invoice.txt',
            'Invoice reference 12345'
        );
        fwrite(STDERR, "STEP 4: file created\n");

        fwrite(STDERR, "STEP 5: before POST\n");

        $response = $this->actingAs($customer)
            ->post(route('tickets.messages.store', $ticket), [
                'attachments' => [$file],
            ]);

        fwrite(STDERR, "STEP 6: after POST, status=".$response->status()."\n");

        $response->assertRedirect(route('tickets.show', $ticket));
        fwrite(STDERR, "STEP 7: redirect assertion passed\n");

        $attachment = TicketAttachment::query()->firstOrFail();
        fwrite(STDERR, "STEP 8: attachment loaded\n");

        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'ticket_message_id' => $attachment->ticket_message_id,
            'user_id' => $customer->id,
            'original_name' => 'invoice.txt',
            'disk' => 'local',
        ]);
        fwrite(STDERR, "STEP 9: database assertion passed\n");

        Storage::disk('local')->assertExists($attachment->path);
        fwrite(STDERR, "STEP 10: stored file exists\n");

        $this->actingAs($customer)
            ->get(route('tickets.attachments.download', [$ticket, $attachment]))
            ->assertDownload('invoice.txt');
        fwrite(STDERR, "STEP 11: download assertion passed\n");
    }

    public function test_customer_cannot_see_or_download_an_attachment_from_an_internal_note(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $ticket = $this->makeTicket($customer);

        $this->actingAs($agent)
            ->post(route('tickets.messages.store', $ticket), [
                'body' => 'Internal investigation details.',
                'is_internal' => true,
                'attachments' => [
                    UploadedFile::fake()->createWithContent('internal-report.txt', 'Private support notes'),
                ],
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $attachment = TicketAttachment::query()->firstOrFail();

        $this->actingAs($customer)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee('internal-report.txt');

        $this->actingAs($customer)
            ->get(route('tickets.attachments.download', [$ticket, $attachment]))
            ->assertNotFound();

        $this->actingAs($agent)
            ->get(route('tickets.attachments.download', [$ticket, $attachment]))
            ->assertDownload('internal-report.txt');
    }

    public function test_upload_rejects_unsupported_file_types(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $ticket = $this->makeTicket($customer);

        $this->actingAs($customer)
            ->from(route('tickets.show', $ticket))
            ->post(route('tickets.messages.store', $ticket), [
                'body' => 'Please check this file.',
                'attachments' => [
                    UploadedFile::fake()->createWithContent('malicious.php', '<?php echo "not allowed";'),
                ],
            ])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHasErrors('attachments.0');

        $this->assertDatabaseCount('ticket_attachments', 0);
    }

    public function test_customer_cannot_download_an_attachment_from_another_ticket(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');
        $ownTicket = $this->makeTicket($customer);
        $otherTicket = $this->makeTicket($otherCustomer);

        $this->actingAs($otherCustomer)
            ->post(route('tickets.messages.store', $otherTicket), [
                'attachments' => [
                    UploadedFile::fake()->createWithContent('private.txt', 'Another customer private file'),
                ],
            ])
            ->assertRedirect(route('tickets.show', $otherTicket));

        $attachment = TicketAttachment::query()->firstOrFail();

        $this->actingAs($customer)
            ->get(route('tickets.attachments.download', [$ownTicket, $attachment]))
            ->assertNotFound();
    }

    private function makeTicket(User $customer): Ticket
    {
        return Ticket::create([
            'subject' => 'Attachment test ticket',
            'description' => 'A ticket used to test private file storage and access.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);
    }
}
