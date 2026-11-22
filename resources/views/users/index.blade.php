@extends('layouts.app')

@section('title', 'User management | HelpDesk')
@section('meta-description', 'Manage HelpDesk accounts, review user access and maintain the administrators, agents and customers in your workspace.')
@section('page-title', 'User management')

@section('content')
    <livewire:users.index />
@endsection
