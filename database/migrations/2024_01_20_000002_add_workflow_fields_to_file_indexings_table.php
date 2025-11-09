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
        Schema::connection('sqlsrv')->table('file_indexings', function (Blueprint $table) {
            // Add workflow fields if they don't exist
            if (!Schema::connection('sqlsrv')->hasColumn('file_indexings', 'is_updated')) {
                $table->boolean('is_updated')->default(false);
            }
            if (!Schema::connection('sqlsrv')->hasColumn('file_indexings', 'batch_id')) {
                $table->string('batch_id')->nullable();
            }
            if (!Schema::connection('sqlsrv')->hasColumn('file_indexings', 'has_qc_issues')) {
                $table->boolean('has_qc_issues')->default(false);
            }
            if (!Schema::connection('sqlsrv')->hasColumn('file_indexings', 'workflow_status')) {
                $table->string('workflow_status')->default('indexed'); // indexed, uploaded, pagetyped, qc_passed, archived
            }
            if (!Schema::connection('sqlsrv')->hasColumn('file_indexings', 'archived_at')) {
                $table->timestamp('archived_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('sqlsrv')->table('file_indexings', function (Blueprint $table) {
            $table->dropColumn([
                'is_updated',
                'batch_id',
                'has_qc_issues',
                'workflow_status',
                'archived_at'
            ]);
        });
    }
};