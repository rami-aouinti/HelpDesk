<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_satisfaction_surveys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->index(['submitted_at', 'rating']);
            $table->index(['agent_id', 'submitted_at']);
            $table->index(['team_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_satisfaction_surveys');
    }
};
