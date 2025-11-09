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
        Schema::connection('sqlsrv')->create('decommissioned_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_number_id');
            $table->string('file_no')->nullable();
            $table->string('mls_file_no')->nullable();
            $table->string('kangis_file_no')->nullable();
            $table->string('new_kangis_file_no')->nullable();
            $table->string('file_name')->nullable();
            $table->datetime('commissioning_date')->nullable();
            $table->datetime('decommissioning_date');
            $table->text('decommissioning_reason');
            $table->string('decommissioned_by');
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('file_number_id')->references('id')->on('fileNumber')->onDelete('cascade');
            
            // Indexes
            $table->index('file_number_id');
            $table->index('mls_file_no');
            $table->index('decommissioning_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv')->dropIfExists('decommissioned_files');
    }
};