<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sla_alert_outbox', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('sla_type', 32);
            $table->string('state', 16);
            $table->timestamp('due_at');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('available_at')->index();
            $table->timestamp('dispatched_at')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['ticket_id', 'sla_type', 'state'], 'sla_alert_outbox_dedupe');
            $table->index(['dispatched_at', 'available_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_alert_outbox');
    }
};
