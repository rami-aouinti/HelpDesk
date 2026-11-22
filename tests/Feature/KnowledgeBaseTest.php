<?php

namespace Tests\Feature;

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_article_is_visible_in_help_centre(): void
    {
        $user = User::factory()->create();
        $category = KnowledgeBaseCategory::query()->create(['name' => 'Accounts', 'slug' => 'accounts']);
        $article = KnowledgeBaseArticle::query()->create([
            'category_id' => $category->id, 'author_id' => $user->id,
            'title' => 'Reset your password', 'slug' => 'reset-your-password',
            'excerpt' => 'Learn how to reset access.', 'content' => 'Follow these steps.',
            'status' => 'published', 'published_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)->get(route('knowledge-base.index'))->assertOk()->assertSee($article->title);
        $this->get(route('knowledge-base.show', $article))->assertOk()->assertSee($article->title);
    }

    public function test_draft_article_is_not_publicly_accessible(): void
    {
        $user = User::factory()->create();
        $article = KnowledgeBaseArticle::query()->create([
            'author_id' => $user->id, 'title' => 'Internal draft', 'slug' => 'internal-draft',
            'content' => 'Not public.', 'status' => 'draft',
        ]);

        $this->actingAs($user)->get(route('knowledge-base.show', $article))->assertNotFound();
    }

    public function test_suggestions_only_return_published_articles(): void
    {
        $user = User::factory()->create();
        KnowledgeBaseArticle::query()->create([
            'author_id' => $user->id, 'title' => 'Configure email notifications',
            'slug' => 'configure-email-notifications', 'content' => 'Enable email notifications.',
            'status' => 'published', 'published_at' => now()->subMinute(),
        ]);
        KnowledgeBaseArticle::query()->create([
            'author_id' => $user->id, 'title' => 'Email notification draft',
            'slug' => 'email-notification-draft', 'content' => 'Draft only.', 'status' => 'draft',
        ]);

        $this->actingAs($user)->getJson(route('knowledge-base.suggestions', ['query' => 'email notifications']))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Configure email notifications');
    }
}
