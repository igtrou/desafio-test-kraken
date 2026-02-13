<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table('quotations', function (Blueprint $table) {
            $table->string('status', 20)->default('valid')->after('source');
            $table->string('invalid_reason', 100)->nullable()->after('status');
            $table->timestamp('invalidated_at')->nullable()->after('invalid_reason');

            $table->index(['status', 'quoted_at'], 'quotations_status_quoted_at_idx');
            $table->index(['asset_id', 'source', 'quoted_at'], 'quotations_asset_source_quoted_at_idx');
            $table->index(['asset_id', 'source', 'quoted_at', 'price', 'currency'], 'quotations_dedupe_lookup_idx');
        });

        // Normalizes existing rows created before status tracking.
        DB::table('quotations')
            ->whereNull('status')
            ->update([
                'status' => 'valid',
                'updated_at' => now(),
            ]);
    }

    /**
     * Executa a rotina principal do metodo down.
     */
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropIndex('quotations_status_quoted_at_idx');
            $table->dropIndex('quotations_asset_source_quoted_at_idx');
            $table->dropIndex('quotations_dedupe_lookup_idx');

            $table->dropColumn([
                'status',
                'invalid_reason',
                'invalidated_at',
            ]);
        });
    }
};
