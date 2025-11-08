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
        Schema::connection('sqlsrv')->table('pagetypings', function (Blueprint $table) {
            // Add QC fields if they don't exist
            if (!Schema::connection('sqlsrv')->hasColumn('pagetypings', 'qc_status')) {
                $table->string('qc_status')->default('pending'); // pending, passed, failed, overridden
            }
            if (!Schema::connection('sqlsrv')->hasColumn('pagetypings', 'qc_reviewed_by')) {
                $table->unsignedBigInteger('qc_reviewed_by')->nullable();
            }
            if (!Schema::connection('sqlsrv')->hasColumn('pagetypings', 'qc_reviewed_at')) {
                $table->timestamp('qc_reviewed_at')->nullable();
            }
            if (!Schema::connection('sqlsrv')->hasColumn('pagetypings', 'qc_overridden')) {
                $table->boolean('qc_overridden')->default(false);
            }
            if (!Schema::connection('sqlsrv')->hasColumn('pagetypings', 'qc_override_note')) {
                $table->text('qc_override_note')->nullable();
            }
            if (!Schema::connection('sqlsrv')->hasColumn('pagetypings', 'has_qc_issues')) {
                $table->boolean('has_qc_issues')->default(false);
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
        Schema::connection('sqlsrv')->table('pagetypings', function (Blueprint $table) {
            $table->dropColumn([
                'qc_status',
                'qc_reviewed_by', 
                'qc_reviewed_at',
                'qc_overridden',
                'qc_override_note',
                'has_qc_issues'
            ]);
        });
    }
};