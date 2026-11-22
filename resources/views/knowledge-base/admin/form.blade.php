@extends('layouts.app')
@section('title', $article->exists ? 'Edit Article' : 'Create Article')
@section('page-title', 'Knowledge Base')
@section('content')
<div class="page-header"><div><h1>{{ $article->exists ? 'Edit article' : 'Create article' }}</h1><p>Write clear, searchable guidance for support users.</p></div><a class="btn btn-secondary" href="{{ route('knowledge-base.admin.index') }}">Back</a></div>
@if ($errors->any())<div class="alert alert-error"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="content-card">
<form method="POST" enctype="multipart/form-data" action="{{ $article->exists ? route('knowledge-base.admin.update',$article) : route('knowledge-base.admin.store') }}">
@csrf @if ($article->exists) @method('PUT') @endif
<div class="form-group"><label for="title">Title</label><input id="title" name="title" value="{{ old('title',$article->title) }}" maxlength="180" required></div>
<div class="form-group"><label for="excerpt">Summary</label><textarea id="excerpt" name="excerpt" rows="2" maxlength="500">{{ old('excerpt',$article->excerpt) }}</textarea></div>
<div class="form-group"><label for="category_name">Category</label><input id="category_name" name="category_name" list="kb-categories" value="{{ old('category_name',$article->category?->name) }}" placeholder="Choose or create a category"><datalist id="kb-categories">@foreach ($categories as $category)<option value="{{ $category->name }}">@endforeach</datalist><small>New category names are created automatically.</small></div>
<div class="form-group"><label for="tags">Tags</label><input id="tags" name="tags" value="{{ old('tags',$tagsValue) }}" placeholder="account, password, security"><small>Separate tags with commas.</small></div>
<div class="form-group"><label for="content">Content</label><textarea id="content" name="content" rows="18" required>{{ old('content',$article->content) }}</textarea><small>Markdown supported: headings, lists, links, code blocks, bold and images. Raw HTML is stripped when rendered.</small></div>
<div class="form-group"><label for="cover">Cover image</label><input id="cover" type="file" name="cover" accept="image/png,image/jpeg,image/webp">@if($article->cover_image_path)<img src="{{ Storage::disk('public')->url($article->cover_image_path) }}" alt="Cover" style="max-width:220px;margin-top:10px;border-radius:10px">@endif</div>
<div class="form-group"><label for="attachments">Attachments</label><input id="attachments" type="file" name="attachments[]" multiple><small>Up to 8 files, 10 MB each. PDF, Office, images, text, CSV and ZIP.</small>@if($article->exists)<ul>@foreach($article->attachments as $attachment)<li>{{ $attachment->original_name }}</li>@endforeach</ul>@endif</div>
<div class="form-group"><label for="status">Status</label><select id="status" name="status">@foreach(['draft'=>'Save draft','published'=>'Publish','archived'=>'Archive'] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$article->status)===$value)>{{ $label }}</option>@endforeach</select></div>
<div style="display:flex;justify-content:flex-end;gap:10px"><a class="btn btn-secondary" href="{{ route('knowledge-base.admin.index') }}">Cancel</a><button class="btn btn-primary">{{ $article->exists ? 'Save changes' : 'Create article' }}</button></div>
</form></div>
@endsection
