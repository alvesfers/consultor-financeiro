<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('client_id')
                ->nullable()
                ->after('id')
                ->constrained('clients')
                ->nullOnDelete();

            // Opcional: se já não existir
            $table->foreignId('parent_id')
                ->nullable()
                ->change();

            // Evita nomes duplicados no mesmo nível do mesmo cliente (ou global)
            $table->unique(['client_id', 'parent_id', 'name'], 'ux_cat_client_parent_name');

            $table->index(['client_id', 'parent_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('type', [
                'income',          // Receita
                'expense',         // Despesa
                'transfer_in',     // Transferência/Saldo (entrada)
                'transfer_out',    // Transferência/Saldo (saída)
                'inv_aporte',      // Investimento - Aplicação
                'inv_resgate',     // Investimento - Resgate
                'inv_rendimento',  // Investimento - Rendimento
            ])->nullable()->after('status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('ux_cat_client_parent_name');
            $table->dropIndex(['client_id', 'parent_id']);

            $table->dropConstrainedForeignId('client_id');
        });
    }
};
