<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('project_documents', function (Blueprint $table): void { $table->id(); $table->foreignId('project_id')->constrained()->cascadeOnDelete(); $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete(); $table->string('disk')->default('local'); $table->string('path'); $table->string('original_name'); $table->string('mime_type',150)->nullable(); $table->unsignedBigInteger('size'); $table->timestamps(); $table->index(['project_id','created_at']); }); }
 public function down(): void { Schema::dropIfExists('project_documents'); }
};