<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->timestamp('first_response_due_at')->nullable()->index();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('first_response_warning_sent_at')->nullable();
            $table->timestamp('first_response_breached_at')->nullable();
            $table->timestamp('resolution_due_at')->nullable()->index();
            $table->timestamp('resolution_warning_sent_at')->nullable();
            $table->timestamp('resolution_breached_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn([
                'first_response_due_at',
                'first_response_at',
                'first_response_warning_sent_at',
                'first_response_breached_at',
                'resolution_due_at',
                'resolution_warning_sent_at',
                'resolution_breached_at',
            ]);
        });
    }
};
