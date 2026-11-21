@extends('layouts.app')

@section('title', 'Help center | HelpDesk')
@section('page-title', 'Help center')

@section('content')
    <div class="page-header">
        <div>
            <h1>Help center</h1>
            <p>Learn how to use HelpDesk and understand the available features.</p>
        </div>
    </div>

    <section class="content-card">
        <h2>Getting started</h2>
        <p>HelpDesk is a support platform where customers can report issues and follow their resolution. Support agents manage incoming requests, communicate with customers and keep track of progress.</p>
    </section>

    <section class="content-card" style="margin-top:20px;">
        <h2>Roles and permissions</h2>
        <div style="margin-top:14px;">
            <h3>Administrator</h3>
            <p>Manages users, roles, teams and tickets. Administrators can assign agents and update ticket status and priority.</p>
            <h3 style="margin-top:16px;">Support agent</h3>
            <p>Views support tickets, replies to customers, adds private internal notes and manages ticket assignments and progress.</p>
            <h3 style="margin-top:16px;">Customer</h3>
            <p>Creates tickets, views only their own tickets, replies to support and downloads attachments shared in their conversations.</p>
        </div>
    </section>

    <section class="content-card" style="margin-top:20px;">
        <h2>Tickets and workflow</h2>
        <p>Every ticket has a subject, description, priority, status and optional team or assigned agent.</p>
        <ul style="padding-left:22px;list-style:disc;margin-top:10px;">
            <li><strong>Open:</strong> the request has been created and is waiting for support.</li>
            <li><strong>In progress:</strong> a support agent is working on it.</li>
            <li><strong>Waiting for customer:</strong> support needs more information from the customer.</li>
            <li><strong>Resolved:</strong> a solution has been provided.</li>
            <li><strong>Closed:</strong> the ticket is no longer active.</li>
        </ul>
        <p style="margin-top:12px;">Ticket updates are recorded in the activity history so the support team can see what changed and when.</p>
    </section>

    <section class="content-card" style="margin-top:20px;">
        <h2>Conversation</h2>
        <h3 style="margin-top:14px;">Internal notes</h3>
        <p>Use the conversation area on a ticket to communicate with the other side. Normal replies are visible to the customer and support team.</p>
        <p style="margin-top:10px;">Agents and administrators can select <strong>Internal note</strong> for information intended only for the support team. Customers cannot view internal notes.</p>
    </section>

    <section class="content-card" style="margin-top:20px;">
        <h2>Attachments</h2>
        <p>You can attach up to 5 files to a conversation message. Each file can be up to 10 MB.</p>
        <p style="margin-top:10px;">Supported formats: PDF, JPG, PNG, TXT, CSV, DOC, DOCX, XLS and XLSX. Files are stored privately and can only be downloaded by people authorized to view the ticket. Attachments belonging to internal notes are hidden from customers.</p>
    </section>

    <section class="content-card" style="margin-top:20px;">
        <h2>Teams and assignment</h2>
        <p>Administrators and agents with the required permission can assign a ticket to a team and one of its active members. The assigned agent must belong to the selected team.</p>
    </section>

    <section class="content-card" style="margin-top:20px;">
        <h2>Your profile</h2>
        <p>Open <strong>My profile</strong> to change your name, email address or profile photo. A new email address must be verified before you can continue using protected pages.</p>
    </section>

    <section class="content-card" style="margin-top:20px;">
        <h2>Need more help?</h2>
        <p>If you are a customer, create a ticket describing the problem and include any relevant screenshots or documents. If you are a support agent, add an internal note when you need to share information with colleagues without exposing it to the customer.</p>
        <a href="{{ route('tickets.index') }}" class="btn btn-primary" style="display:inline-block;margin-top:14px;">Go to tickets</a>
    </section>
@endsection
