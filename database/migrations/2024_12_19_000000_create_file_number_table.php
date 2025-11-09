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
        Schema::connection('sqlsrv')->create('fileNumber', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->string('kangisFileNo')->nullable();
            $table->string('mlsfNo')->nullable();
            $table->string('NewKANGISFileNo')->nullable();
            $table->string('FileName')->nullable();
            $table->string('SOURCE')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->datetime('commissioning_date')->nullable();
            $table->datetime('decommissioning_date')->nullable();
            $table->text('decommissioning_reason')->nullable();
            $table->boolean('is_decommissioned')->default(false);
            $table->timestamps();
            
            // Indexes
            $table->index('mlsfNo');
            $table->index('kangisFileNo');
            $table->index('NewKANGISFileNo');
            $table->index('type');
            $table->index('is_decommissioned');
            $table->index('is_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv')->dropIfExists('fileNumber');
    }
};