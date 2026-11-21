<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->string('subject');
            $table->text('description');

            $table->string('status', 30)->default('open');
            $table->string('priority', 20)->default('normal');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
