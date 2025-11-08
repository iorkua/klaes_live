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
        Schema::connection('sqlsrv')->table('fileNumber', function (Blueprint $table) {
            $table->datetime('commissioning_date')->nullable();
            $table->datetime('decommissioning_date')->nullable();
            $table->text('decommissioning_reason')->nullable();
            $table->boolean('is_decommissioned')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv')->table('fileNumber', function (Blueprint $table) {
            $table->dropColumn([
                'commissioning_date',
                'decommissioning_date', 
                'decommissioning_reason',
                'is_decommissioned'
            ]);
        });
    }
};