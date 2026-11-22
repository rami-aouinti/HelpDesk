<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseAttachment;
use App\Models\KnowledgeBaseCategory;
use App\Models\KnowledgeBaseTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request): View
    {
        $articles = KnowledgeBaseArticle::query()->with(['category', 'author', 'tags'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $term = trim((string) $request->input('search'));
                $query->where(fn ($nested) => $nested->where('title', 'like', "%{$term}%")
                    ->orWhere('excerpt', 'like', "%{$term}%")->orWhere('content', 'like', "%{$term}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest('updated_at')->paginate(12)->withQueryString();

        return view('knowledge-base.admin.index', ['articles' => $articles]);
    }

    public function publicIndex(Request $request): View
    {
        $articles = KnowledgeBaseArticle::query()->published()->with(['category', 'tags'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $term = trim((string) $request->input('search'));
                $query->where(fn ($nested) => $nested->where('title', 'like', "%{$term}%")
                    ->orWhere('excerpt', 'like', "%{$term}%")->orWhere('content', 'like', "%{$term}%")
                    ->orWhereHas('tags', fn ($tags) => $tags->where('name', 'like', "%{$term}%")));
            })
            ->when($request->filled('category'), fn ($query) => $query->whereHas('category', fn ($category) => $category->where('slug', $request->input('category'))))
            ->latest('published_at')->paginate(12)->withQueryString();

        $categories = KnowledgeBaseCategory::query()
            ->whereHas('articles', fn ($query) => $query->published())
            ->withCount(['articles as published_articles_count' => fn ($query) => $query->published()])
            ->orderBy('sort_order')->orderBy('name')->get();
        $popularArticles = KnowledgeBaseArticle::query()->published()->orderByDesc('view_count')->limit(5)->get();

        return view('knowledge-base.public.index', ['articles' => $articles, 'categories' => $categories, 'popularArticles' => $popularArticles]);
    }

    public function show(KnowledgeBaseArticle $article): View
    {
        abort_unless($article->status === 'published' && $article->published_at?->lte(now()), 404);
        $article->increment('view_count');
        $article->load(['category', 'tags', 'attachments', 'author']);
        $related = KnowledgeBaseArticle::query()->published()->where('id', '!=', $article->id)
            ->when($article->category_id, fn ($query) => $query->where('category_id', $article->category_id))
            ->latest('published_at')->limit(4)->get();

        return view('knowledge-base.public.show', ['article' => $article, 'related' => $related]);
    }

    public function create(): View
    {
        return view('knowledge-base.admin.form', [
            'article' => new KnowledgeBaseArticle(['status' => 'draft']),
            'categories' => KnowledgeBaseCategory::query()->orderBy('name')->get(),
            'tagsValue' => '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $category = $this->resolveCategory($data['category_name'] ?? null);
        $article = KnowledgeBaseArticle::query()->create([
            'category_id' => $category?->id,
            'author_id' => (int) $request->user()->getKey(),
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'],
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? now() : null,
            'archived_at' => $data['status'] === 'archived' ? now() : null,
        ]);
        $this->saveMedia($request, $article);
        $this->syncTags($article, $data['tags'] ?? '');

        return redirect()->route('knowledge-base.admin.index')->with('success', 'Article created successfully.');
    }

    public function edit(KnowledgeBaseArticle $article): View
    {
        $article->load('tags');

        return view('knowledge-base.admin.form', [
            'article' => $article,
            'categories' => KnowledgeBaseCategory::query()->orderBy('name')->get(),
            'tagsValue' => $article->tags->pluck('name')->implode(', '),
        ]);
    }

    public function update(Request $request, KnowledgeBaseArticle $article): RedirectResponse
    {
        $data = $this->validatedData($request);
        $category = $this->resolveCategory($data['category_name'] ?? null);
        $article->fill([
            'category_id' => $category?->id,
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'],
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? ($article->published_at ?? now()) : null,
            'archived_at' => $data['status'] === 'archived' ? ($article->archived_at ?? now()) : null,
        ]);
        if ($article->isDirty('title')) {
            $article->slug = $this->uniqueSlug($data['title'], (int) $article->getKey());
        }
        $article->save();
        $this->saveMedia($request, $article);
        $this->syncTags($article, $data['tags'] ?? '');

        return redirect()->route('knowledge-base.admin.index')->with('success', 'Article updated successfully.');
    }

    public function destroy(KnowledgeBaseArticle $article): RedirectResponse
    {
        foreach ($article->attachments as $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }
        if ($article->cover_image_path) {
            Storage::disk('public')->delete($article->cover_image_path);
        }
        $article->delete();

        return redirect()->route('knowledge-base.admin.index')->with('success', 'Article deleted successfully.');
    }

    public function suggestions(Request $request): JsonResponse
    {
        $data = $request->validate(['query' => ['required', 'string', 'min:3', 'max:300']]);
        $terms = collect(preg_split('/\s+/u', trim($data['query'])) ?: [])
            ->filter(fn (string $term): bool => mb_strlen($term) >= 3)->take(8)->values();

        if ($terms->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $articles = KnowledgeBaseArticle::query()->published()
            ->where(function ($query) use ($terms): void {
                foreach ($terms as $term) {
                    $query->orWhere('title', 'like', "%{$term}%")
                        ->orWhere('excerpt', 'like', "%{$term}%")
                        ->orWhere('content', 'like', "%{$term}%");
                }
            })->with('category')->orderByDesc('view_count')->limit(5)->get();

        return response()->json(['data' => $articles->map(fn (KnowledgeBaseArticle $article): array => [
            'title' => $article->title,
            'excerpt' => Str::limit((string) ($article->excerpt ?: strip_tags($article->content)), 120),
            'url' => route('knowledge-base.show', $article),
            'category' => $article->category?->name,
        ])]);
    }

    public function download(KnowledgeBaseAttachment $attachment)
    {
        abort_unless($attachment->article()->published()->exists(), 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:100000'],
            'category_name' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'cover' => ['nullable', 'image', 'max:5120'],
            'attachments' => ['nullable', 'array', 'max:8'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,webp,txt,csv,zip'],
        ]);
    }

    private function resolveCategory(?string $name): ?KnowledgeBaseCategory
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $slug = Str::slug($name);
        return KnowledgeBaseCategory::query()->firstOrCreate(['slug' => $slug], ['name' => $name]);
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base;
        $counter = 2;
        while (
            KnowledgeBaseArticle::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }

    private function syncTags(KnowledgeBaseArticle $article, string $tags): void
    {
        $tagIds = collect(explode(',', $tags))->map(fn (string $tag) => trim($tag))->filter()
            ->unique(fn (string $tag) => mb_strtolower($tag))->take(20)
            ->map(function (string $name): int {
                $tag = KnowledgeBaseTag::query()->firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name],
                );

                return (int) $tag->getKey();
            });
        $article->tags()->sync($tagIds);
    }

    private function saveMedia(Request $request, KnowledgeBaseArticle $article): void
    {
        if ($request->hasFile('cover')) {
            if ($article->cover_image_path) {
                Storage::disk('public')->delete($article->cover_image_path);
            }
            $article->cover_image_path = $request->file('cover')->store('knowledge-base/covers', 'public');
            $article->save();
        }

        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('knowledge-base/attachments', 'local');
            $article->attachments()->create([
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
            ]);
        }
    }
}
