<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            // Supports customer-scoped ticket pagination ordered by newest first.
            $table->index(['user_id', 'created_at'], 'tickets_user_id_created_at_index');

            // Supports globally visible ticket lists ordered by creation time.
            $table->index('created_at', 'tickets_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropIndex('tickets_user_id_created_at_index');
            $table->dropIndex('tickets_created_at_index');
        });
    }
};
