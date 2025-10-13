<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Mês de fatura no formato YYYY-MM (só é usado quando card_id != null)
            $table->char('invoice_month', 7)->nullable()->index()->after('card_id');

            // Parcelas
            $table->unsignedInteger('installment_count')->nullable()->after('amount'); // total de parcelas (ex.: 10)
            $table->unsignedInteger('installment_index')->nullable()->after('installment_count'); // começa em 1
            $table->foreignId('parent_transaction_id')->nullable()->constrained('transactions')->cascadeOnDelete();

            // Marcação de "quitada" para fatura (opcional p/ conciliação)
            $table->boolean('invoice_paid')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['invoice_month', 'installment_count', 'installment_index', 'parent_transaction_id', 'invoice_paid']);
        });
    }
};
