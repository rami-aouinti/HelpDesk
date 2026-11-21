<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\Team;
use App\Models\User;
use App\Services\TicketService;
use App\Services\TicketNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TicketController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->can('viewAny', Ticket::class), 403);

        return view('tickets.index');
    }

    public function create(): View
    {
        abort_unless(auth()->user()->can('create', Ticket::class), 403);

        return view('tickets.create');
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = Ticket::create([
            ...$request->validated(),
            'status' => 'open',
            'user_id' => $request->user()->id,
        ]);

        app(TicketNotificationService::class)->ticketCreated($ticket);

        return redirect()
            ->route('tickets.index')
            ->with('success', "Ticket #{$ticket->id} created successfully.");
    }

    public function show(Ticket $ticket): View
    {
        abort_unless(auth()->user()->can('view', $ticket), 403);

        $ticket->load(['customer', 'assignedAgent', 'team', 'histories.user']);

        $messagesQuery = $ticket->messages()->with(['user', 'attachments'])->oldest();
        if (auth()->user()->hasRole('customer')) {
            $messagesQuery->where('is_internal', false);
        }

        return view('tickets.show', [
            'ticket' => $ticket,
            'messages' => $messagesQuery->get(),
            'teams' => Team::query()->with('members')->orderBy('name')->get(),
            'agents' => User::query()->role('agent')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeMessage(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($request->user()->can('view', $ticket), 403);

        $data = $request->validate([
            'body' => ['nullable', 'required_without:attachments', 'string', 'min:2', 'max:10000'],
            'is_internal' => ['sometimes', 'boolean'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,txt,csv,doc,docx,xls,xlsx',
                'extensions:pdf,jpg,jpeg,png,txt,csv,doc,docx,xls,xlsx',
            ],
        ]);

        $isInternal = $request->boolean('is_internal');
        if ($isInternal) {
            abort_unless($request->user()->can('update', $ticket), 403);
        }

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'body' => trim((string) ($data['body'] ?? '')) ?: 'Shared attachments.',
            'is_internal' => $isInternal,
        ]);

        $storedPaths = [];

        try {
            foreach ($request->file('attachments', []) as $file) {
                $path = $file->store("ticket-attachments/{$ticket->id}", 'local');

                if ($path === false) {
                    throw new \RuntimeException('The attachment could not be stored.');
                }

                $storedPaths[] = $path;

                $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));

                $message->attachments()->create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $request->user()->id,
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => mb_strcut($originalName, 0, 255, 'UTF-8'),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            $message->delete();

            throw $exception;
        }

        app(TicketNotificationService::class)->messageCreated($message);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', $isInternal ? 'Internal note added.' : 'Reply sent successfully.');
    }

    public function downloadAttachment(
        Request $request,
        Ticket $ticket,
        TicketAttachment $attachment
    ): StreamedResponse {
        abort_unless($request->user()->can('view', $ticket), 403);
        abort_unless($attachment->ticket_id === $ticket->id, 404);

        if (
            $request->user()->hasRole('customer')
            && $attachment->message()->where('is_internal', true)->exists()
        ) {
            abort(404);
        }

        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download(
            $attachment->path,
            basename($attachment->original_name),
            [
                'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * @throws Throwable
     */
    public function update(
        UpdateTicketRequest $request,
        Ticket $ticket,
        TicketService $ticketService
    ): RedirectResponse {
        $ticketService->update($ticket, $request->validated(), $request->user()->id);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket updated successfully.');
    }
}
