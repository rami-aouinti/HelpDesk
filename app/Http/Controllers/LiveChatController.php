<?php

namespace App\Http\Controllers;

use App\Models\AgentPresence;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LiveChatController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasAnyRole(['customer', 'agent', 'admin']), 403);
        return view('live-chat.index', ['isAgent' => ! $request->user()->hasRole('customer')]);
    }

    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->hasRole('customer')) {
            $items = ChatConversation::query()->where('customer_id', $user->id)->whereIn('status', ['open', 'closed'])->latest('last_message_at')->limit(10)->get();
        } else {
            $items = ChatConversation::query()->with('customer:id,name,profile_photo_path')
                ->where('status', 'open')
                ->where(function ($query) use ($user): void {
                    $query->whereNull('agent_id')->orWhere('agent_id', $user->id);
                })->latest('last_message_at')->limit(50)->get();
        }

        return response()->json(['conversations' => $items->map(fn (ChatConversation $item) => [
            'id' => $item->id,
            'subject' => $item->subject ?: 'Customer conversation',
            'status' => $item->status,
            'customer' => $item->customer?->name,
            'agent_id' => $item->agent_id,
            'last_message_at' => $item->last_message_at?->toIso8601String(),
        ])]);
    }

    public function start(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('customer'), 403);
        $data = $request->validate(['subject' => ['nullable', 'string', 'max:160']]);

        $conversation = ChatConversation::query()->firstOrCreate(
            ['customer_id' => $request->user()->id, 'status' => 'open'],
            ['subject' => $data['subject'] ?? null],
        );

        return response()->json(['conversation_id' => $conversation->id], $conversation->wasRecentlyCreated ? 201 : 200);
    }

    public function messages(Request $request, ChatConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);
        return response()->json([
            'conversation' => ['id' => $conversation->id, 'status' => $conversation->status, 'agent_id' => $conversation->agent_id],
            'messages' => $conversation->messages()->with('user:id,name,profile_photo_path')->get()->map(fn (ChatMessage $message) => [
                'id' => $message->id,
                'user_id' => $message->user_id,
                'user' => $message->user?->name,
                'body' => $message->body,
                'created_at' => $message->created_at?->format('H:i'),
            ]),
        ]);
    }

    public function send(Request $request, ChatConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);
        abort_unless($conversation->status === 'open', 409, 'This conversation is closed.');
        $data = $request->validate(['body' => ['required', 'string', 'min:1', 'max:5000']]);

        $message = DB::transaction(function () use ($request, $conversation, $data): ChatMessage {
            $message = $conversation->messages()->create([
                'user_id' => $request->user()->id,
                'body' => trim($data['body']),
            ]);
            $conversation->forceFill(['last_message_at' => now()])->save();
            return $message;
        });

        return response()->json(['message_id' => $message->id], 201);
    }

    public function claim(Request $request, ChatConversation $conversation): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['agent', 'admin']), 403);
        abort_unless($conversation->status === 'open', 409);
        abort_if($conversation->agent_id !== null && $conversation->agent_id !== $request->user()->id, 409, 'Another agent already owns this conversation.');

        $conversation->forceFill(['agent_id' => $request->user()->id])->save();
        return response()->json(['success' => true]);
    }

    public function presence(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['agent', 'admin']), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['available', 'busy', 'offline'])]]);
        AgentPresence::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['status' => $data['status'], 'last_seen_at' => now()],
        );
        return response()->json(['success' => true]);
    }

    public function agents(): JsonResponse
    {
        $agents = AgentPresence::query()->with('user:id,name,profile_photo_path')
            ->whereIn('status', ['available', 'busy'])
            ->where('last_seen_at', '>=', now()->subMinutes(2))
            ->get()
            ->map(fn (AgentPresence $presence) => [
                'name' => $presence->user?->name,
                'status' => $presence->status,
            ]);

        return response()->json(['agents' => $agents]);
    }

    public function convert(Request $request, ChatConversation $conversation): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['agent', 'admin']), 403);
        abort_unless($conversation->status === 'open', 409);
        abort_if($conversation->converted_ticket_id !== null, 409);

        $ticket = DB::transaction(function () use ($conversation, $request): Ticket {
            $transcript = $conversation->messages()->with('user')->get()
                ->map(fn (ChatMessage $message) => '[' . $message->created_at->format('Y-m-d H:i') . '] ' . $message->user->name . ': ' . $message->body)
                ->implode("\n");
            $ticket = Ticket::query()->create([
                'subject' => $conversation->subject ?: 'Live chat conversation #' . $conversation->id,
                'description' => "Conversation converted from Live Chat.\n\n" . $transcript,
                'status' => 'open',
                'priority' => 'normal',
                'user_id' => $conversation->customer_id,
                'assigned_to' => $conversation->agent_id ?: $request->user()->id,
            ]);
            $conversation->forceFill(['status' => 'converted', 'converted_ticket_id' => $ticket->id])->save();
            return $ticket;
        });

        return redirect()->route('tickets.show', $ticket)->with('success', 'Chat conversation converted to ticket #' . $ticket->id . '.');
    }

    private function authorizeConversation(Request $request, ChatConversation $conversation): void
    {
        $user = $request->user();
        if ($user->hasRole('customer')) {
            abort_unless($conversation->customer_id === $user->id, 404);
            return;
        }
        abort_unless($user->hasAnyRole(['agent', 'admin']), 403);
        if ($conversation->agent_id !== null && $conversation->agent_id !== $user->id && ! $user->hasRole('admin')) {
            abort(403);
        }
    }
}
