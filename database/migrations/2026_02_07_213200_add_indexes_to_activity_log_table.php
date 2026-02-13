<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa a rotina principal do metodo up.
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table): void {
                $table->index('event', 'activity_log_event_idx');
                $table->index('created_at', 'activity_log_created_at_idx');
                $table->index(['causer_type', 'causer_id'], 'activity_log_causer_idx');
                $table->index(['subject_type', 'subject_id'], 'activity_log_subject_idx');
            });
    }

    /**
     * Executa a rotina principal do metodo down.
     */
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table): void {
                $table->dropIndex('activity_log_event_idx');
                $table->dropIndex('activity_log_created_at_idx');
                $table->dropIndex('activity_log_causer_idx');
                $table->dropIndex('activity_log_subject_idx');
            });
    }
};
