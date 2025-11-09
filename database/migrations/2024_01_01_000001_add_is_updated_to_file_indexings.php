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
        // Check if the column already exists
        if (!Schema::connection('sqlsrv')->hasColumn('file_indexings', 'is_updated')) {
            Schema::connection('sqlsrv')->table('file_indexings', function (Blueprint $table) {
                $table->boolean('is_updated')->default(false)->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('sqlsrv')->hasColumn('file_indexings', 'is_updated')) {
            Schema::connection('sqlsrv')->table('file_indexings', function (Blueprint $table) {
                $table->dropColumn('is_updated');
            });
        }
    }
};