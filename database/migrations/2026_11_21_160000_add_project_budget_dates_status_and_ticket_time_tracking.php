<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->decimal('budget', 12, 2)->nullable()->after('description');
            $table->date('start_date')->nullable()->after('budget');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('status', 30)->default('planned')->index();
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->unsignedInteger('estimated_minutes')->default(0)->after('project_id');
            $table->unsignedInteger('elapsed_minutes')->default(0)->after('estimated_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn(['estimated_minutes', 'elapsed_minutes']);
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn(['budget', 'start_date', 'end_date', 'status']);
        });
    }
};
