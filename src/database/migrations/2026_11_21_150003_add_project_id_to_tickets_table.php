<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('tickets', function (Blueprint $table): void { $table->foreignId('project_id')->nullable()->after('team_id')->constrained()->nullOnDelete(); $table->index(['project_id','team_id']); }); }
 public function down(): void { Schema::table('tickets', function (Blueprint $table): void { $table->dropIndex(['project_id','team_id']); $table->dropConstrainedForeignId('project_id'); }); }
};