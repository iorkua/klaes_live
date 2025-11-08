<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv')->create('blind_scannings', function (Blueprint $table) {
            $table->id();
            $table->string('temp_file_id')->unique(); // Temporary ID for blind scans
            $table->string('original_filename');
            $table->string('document_path');
            $table->string('paper_size')->nullable();
            $table->string('document_type')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending, converted, archived
            $table->unsignedBigInteger('uploaded_by');
            $table->unsignedBigInteger('file_indexing_id')->nullable(); // Linked after conversion
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'uploaded_by']);
            $table->index('temp_file_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('sqlsrv')->dropIfExists('blind_scannings');
    }
};