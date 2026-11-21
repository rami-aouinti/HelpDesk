<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $teams = config('permission.teams');

        Schema::create('permissions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->bigIncrements('id');
            if (config('permission.teams')) {
                $table->unsignedBigInteger(config('permission.column_names.team_foreign_key'));
                $table->index(config('permission.column_names.team_foreign_key'), 'roles_team_foreign_key_index');
            }
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(
                $teams ? [config('permission.column_names.team_foreign_key'), 'name', 'guard_name'] : ['name', 'guard_name'],
                'roles_name_guard_name_unique'
            );
        });

        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger(config('permission.column_names.model_morph_key'));
            $table->index(
                [config('permission.column_names.model_morph_key'), 'model_type'],
                'model_has_permissions_model_id_model_type_index'
            );
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->primary(
                ['permission_id', config('permission.column_names.model_morph_key'), 'model_type'],
                'model_has_permissions_permission_model_type_primary'
            );
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger(config('permission.column_names.model_morph_key'));
            $table->index(
                [config('permission.column_names.model_morph_key'), 'model_type'],
                'model_has_roles_model_id_model_type_index'
            );
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(
                ['role_id', config('permission.column_names.model_morph_key'), 'model_type'],
                'model_has_roles_role_model_type_primary'
            );
        });

        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['permission_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
