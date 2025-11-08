<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('sqlsrv')->create('sub_application_draft', function (Blueprint $table) {
            $table->id();
            $table->uuid('draft_id')->unique();
            $table->unsignedBigInteger('sub_application_id')->nullable();
            $table->unsignedBigInteger('main_application_id')->nullable();
            $table->json('form_state')->nullable();
            $table->decimal('progress_percent', 5, 2)->default(0.00);
            $table->integer('last_completed_step')->default(1);
            $table->integer('auto_save_frequency')->default(30);
            $table->boolean('is_locked')->default(false);
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->integer('version')->default(1);
            $table->unsignedBigInteger('last_saved_by');
            $table->timestamp('last_saved_at')->nullable();
            $table->json('analytics')->nullable();
            $table->json('collaborators')->nullable();
            $table->text('last_error')->nullable();
            $table->string('unit_file_no')->nullable();
            $table->boolean('is_sua')->default(false);
            $table->timestamps();

            $table->index(['last_saved_by', 'is_sua']);
            $table->index(['main_application_id', 'is_sua']);
            $table->index('unit_file_no');
            $table->index('sub_application_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv')->dropIfExists('sub_application_draft');
    }
};