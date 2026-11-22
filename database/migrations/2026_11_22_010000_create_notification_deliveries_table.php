<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->string('notification_id', 100);
            $table->string('notifiable_type');
            $table->string('notifiable_id', 100);
            $table->string('notification_type');
            $table->string('channel', 50);
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['notification_id', 'notifiable_type', 'notifiable_id', 'channel'],
                'notification_deliveries_identity_unique',
            );
            $table->index(['status', 'updated_at']);
            $table->index(['notifiable_type', 'notifiable_id', 'created_at'], 'notification_deliveries_recipient_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
