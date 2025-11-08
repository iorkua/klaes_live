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
        Schema::connection('sqlsrv')->create('user_activity_log_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // null for global settings
            $table->string('cleanup_interval', 20)->default('weekly'); // daily, weekly, monthly
            $table->integer('retention_days')->default(90); // days to keep logs
            $table->integer('refresh_interval')->default(30); // seconds for auto-refresh
            $table->integer('records_per_page')->default(25); // records per page in table
            $table->boolean('auto_logout_inactive')->default(false); // auto-logout inactive sessions
            $table->boolean('track_failed_logins')->default(true); // track failed login attempts
            $table->boolean('ip_based_alerts')->default(false); // send alerts for new IP addresses
            $table->boolean('email_notifications')->default(false); // send email notifications
            $table->json('settings_data')->nullable(); // additional settings as JSON
            $table->timestamps();

            // Indexes for better performance
            $table->index('user_id');
            $table->unique(['user_id']); // One setting record per user (null for global)

            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('sqlsrv')->dropIfExists('user_activity_log_settings');
    }
};