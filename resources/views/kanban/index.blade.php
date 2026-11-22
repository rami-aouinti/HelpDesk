@extends('layouts.app')

@section('title', 'Kanban')
@section('meta-description', 'Visualize ticket workflow on the HelpDesk Kanban board and follow work as requests move through each stage.')
@section('page-title', 'Kanban')

@section('content')
    <livewire:kanban.board />
@endsection
