@extends('layouts.app')

@section('title', 'Notifications | HelpDesk')
@section('page-title', 'Notifications')

@section('content')
    <div class="notification-inbox">
        <div class="notification-inbox-heading">
            <div>
                <span class="eyebrow">YOUR WORKSPACE</span>
                <h1>Notifications</h1>
                <p>Keep track of ticket updates, assignments and important activity.</p>
            </div>
            <div class="notification-inbox-actions">
                <span class="notification-inbox-unread">{{ $unreadCount }} unread</span>
                @if ($unreadCount > 0)
                    <button type="button" class="btn btn-secondary" id="inbox-mark-all"
                        data-url="{{ route('notifications.read-all') }}">Mark all as read</button>
                @endif
            </div>
        </div>

        <div class="notification-inbox-tabs" aria-label="Notification filters">
            <a href="{{ route('notifications.index') }}" class="{{ $filter === 'all' ? 'active' : '' }}">All notifications</a>
            <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="{{ $filter === 'unread' ? 'active' : '' }}">Unread</a>
        </div>

        <section class="notification-inbox-list" aria-label="Notification history">
            @forelse ($notifications as $notification)
                @php
                    $notificationUrl = $notification->data['url'] ?? route('tickets.index');
                @endphp
                <a class="notification-inbox-item {{ $notification->read_at ? '' : 'is-unread' }}"
                    href="{{ $notificationUrl }}"
                    data-notification-id="{{ $notification->id }}"
                    data-read-url="{{ route('notifications.read', ['notification' => $notification->id]) }}">
                    <span class="notification-inbox-indicator" aria-hidden="true"></span>
                    <span class="notification-inbox-content">
                        <span class="notification-inbox-item-heading">
                            <strong>{{ $notification->data['title'] ?? 'HelpDesk notification' }}</strong>
                            <time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->diffForHumans() }}</time>
                        </span>
                        @if (!empty($notification->data['message']))
                            <span class="notification-inbox-message">{{ $notification->data['message'] }}</span>
                        @endif
                        @if (!empty($notification->data['ticket_subject']))
                            <span class="notification-inbox-ticket">Ticket: {{ $notification->data['ticket_subject'] }}</span>
                        @endif
                    </span>
                    @unless ($notification->read_at)
                        <span class="notification-inbox-status">New</span>
                    @endunless
                </a>
            @empty
                <div class="notification-inbox-empty">
                    <span aria-hidden="true">✓</span>
                    <h2>{{ $filter === 'unread' ? 'You are all caught up' : 'No notifications yet' }}</h2>
                    <p>{{ $filter === 'unread' ? 'There are no unread updates waiting for you.' : 'Updates about your tickets and workspace will appear here.' }}</p>
                </div>
            @endforelse
        </section>

        @if ($notifications->hasPages())
            <div class="notification-inbox-pagination">{{ $notifications->links() }}</div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            document.querySelectorAll('.notification-inbox-item.is-unread').forEach(function (item) {
                item.addEventListener('click', function () {
                    fetch(item.dataset.readUrl, {
                        method: 'PATCH',
                        credentials: 'same-origin',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                    });
                });
            });

            var markAll = document.getElementById('inbox-mark-all');
            if (markAll) {
                markAll.addEventListener('click', function () {
                    markAll.disabled = true;
                    fetch(markAll.dataset.url, {
                        method: 'PATCH',
                        credentials: 'same-origin',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                    }).then(function (response) {
                        if (!response.ok) throw new Error('Unable to mark notifications as read.');
                        window.location.reload();
                    }).catch(function () {
                        markAll.disabled = false;
                        markAll.textContent = 'Please try again';
                    });
                });
            }
        });
    </script>
@endsection
