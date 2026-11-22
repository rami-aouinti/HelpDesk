<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('automation_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger', 60)->index();
            $table->json('conditions');
            $table->json('actions');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('automation_executions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('automation_rule_id')->nullable()->constrained('automation_rules')->nullOnDelete();
            $table->unsignedBigInteger('ticket_id')->nullable()->index();
            $table->string('trigger', 60);
            $table->string('status', 20)->default('pending')->index();
            $table->json('context')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['automation_rule_id', 'created_at']);
        });

        Schema::create('ticket_tags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['ticket_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_tags');
        Schema::dropIfExists('automation_executions');
        Schema::dropIfExists('automation_rules');
    }
};
