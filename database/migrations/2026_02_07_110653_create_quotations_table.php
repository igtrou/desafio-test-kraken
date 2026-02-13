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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 16, 6);
            $table->string('currency', 10);
            $table->string('source', 50);
            $table->timestamp('quoted_at')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'quoted_at']);
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
        Schema::dropIfExists('quotations');
    }
};
