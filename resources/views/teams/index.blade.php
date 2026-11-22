@extends('layouts.app')

@section('title', 'Team management | HelpDesk')
@section('meta-description', 'Organize support teams, manage team membership and coordinate the people responsible for customer requests.')
@section('page-title', 'Team management')

@section('content')
    <livewire:teams.index />
@endsection
