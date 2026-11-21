<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\TicketNotificationService;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        // Notification delivery is covered separately; keep attachment tests focused.
        $this->mock(TicketNotificationService::class)
            ->shouldReceive('messageCreated')
            ->zeroOrMoreTimes();

        Storage::fake('local');
    }

    public function test_customer_can_upload_and_download_an_attachment_on_their_ticket(): void
    {

        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();

        $ticket = $this->makeTicket($customer);

        $file = UploadedFile::fake()->createWithContent(
            'invoice.txt',
            'Invoice reference 12345'
        );

        $response = $this->actingAs($customer)
            ->post(route('tickets.messages.store', $ticket), [
                'attachments' => [$file],
            ]);

        $response->assertRedirect(route('tickets.show', $ticket));

        $attachment = TicketAttachment::query()->firstOrFail();

        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'ticket_message_id' => $attachment->ticket_message_id,
            'user_id' => $customer->id,
            'original_name' => 'invoice.txt',
            'disk' => 'local',
        ]);

        Storage::disk('local')->assertExists($attachment->path);

        $this->actingAs($customer)
            ->get(route('tickets.attachments.download', [$ticket, $attachment]))
            ->assertDownload('invoice.txt');
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
