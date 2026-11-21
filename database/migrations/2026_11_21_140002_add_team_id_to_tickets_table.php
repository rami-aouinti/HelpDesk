<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->foreignId('team_id')
                ->nullable()
                ->after('assigned_to')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('team_id');
        });
    }
};
