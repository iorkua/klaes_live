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
        Schema::connection('sqlsrv')->create('sub_application_draft_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('draft_id');
            $table->integer('version');
            $table->json('snapshot');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['draft_id', 'version']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv')->dropIfExists('sub_application_draft_versions');
    }
};