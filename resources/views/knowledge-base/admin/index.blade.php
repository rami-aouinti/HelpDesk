@extends('layouts.app')
@section('title', 'Knowledge Base Management')
@section('page-title', 'Knowledge Base')
@section('content')
<div class="page-header">
    <div><h1>Knowledge Base</h1><p>Create, publish and maintain helpful support articles.</p></div>
    <a class="btn btn-primary" href="{{ route('knowledge-base.admin.create') }}">+ New article</a>
</div>
@if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
<form method="GET" class="content-card" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end;margin-bottom:20px">
    <div class="form-group" style="flex:1;min-width:220px;margin:0"><label for="search">Search</label><input id="search" name="search" value="{{ request('search') }}" placeholder="Title or content"></div>
    <div class="form-group" style="min-width:150px;margin:0"><label for="status">Status</label><select id="status" name="status"><option value="">All</option>@foreach (['draft','published','archived'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
    <button class="btn btn-secondary">Filter</button>
</form>
<div class="content-card">
@forelse ($articles as $article)
    <div style="display:flex;justify-content:space-between;gap:18px;align-items:center;padding:16px 0;border-bottom:1px solid var(--border-color,#e5e7eb)">
        <div><strong>{{ $article->title }}</strong> <span class="status-badge">{{ ucfirst($article->status) }}</span><p style="margin:5px 0;color:var(--muted-text,#64748b)">{{ $article->category?->name ?? 'Uncategorized' }} · {{ $article->view_count }} views · {{ $article->updated_at->diffForHumans() }}</p><small>{{ $article->tags->pluck('name')->join(' · ') }}</small></div>
        <div style="display:flex;gap:8px"><a class="btn btn-secondary" href="{{ route('knowledge-base.admin.edit',$article) }}">Edit</a><form method="POST" action="{{ route('knowledge-base.admin.update',$article) }}">@csrf @method('PUT')<input type="hidden" name="title" value="{{ $article->title }}"><input type="hidden" name="content" value="{{ $article->content }}"><input type="hidden" name="excerpt" value="{{ $article->excerpt }}"><input type="hidden" name="category_name" value="{{ $article->category?->name }}"><input type="hidden" name="tags" value="{{ $article->tags->pluck('name')->join(',') }}"><input type="hidden" name="status" value="archived"><button class="btn btn-secondary" onclick="return confirm('Archive this article?')">Archive</button></form></div>
    </div>
@empty
    <div style="padding:45px;text-align:center"><h3>No articles yet</h3><p>Create your first help article.</p><a class="btn btn-primary" href="{{ route('knowledge-base.admin.create') }}">Create article</a></div>
@endforelse
<div style="margin-top:20px">{{ $articles->links() }}</div>
</div>
@endsection
