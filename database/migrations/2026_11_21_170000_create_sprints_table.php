<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('sprints', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->foreignId('sprint_id')
                ->nullable()
                ->constrained('sprints')
                ->nullOnDelete();

            $table->index(['sprint_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropForeign(['sprint_id']);
            $table->dropIndex(['sprint_id', 'status']);
            $table->dropColumn('sprint_id');
        });

        Schema::dropIfExists('sprints');
    }
};
