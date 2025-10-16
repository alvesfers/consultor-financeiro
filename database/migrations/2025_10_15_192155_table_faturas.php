<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_invoices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('client_id')->index();
            $t->unsignedBigInteger('card_id')->index();
            $t->date('month_ref');             // sempre dia 1 (YYYY-MM-01)
            $t->date('cycle_start');           // inclusive
            $t->date('cycle_end');             // inclusive (dia close_day)
            $t->date('due_on');                // dia due_day (ajustado para fim do mês se precisar)
            $t->decimal('total_amount', 14, 2)->default(0);
            $t->decimal('paid_amount', 14, 2)->default(0);
            $t->decimal('remaining_amount', 14, 2)->default(0);
            $t->enum('status', ['open', 'closed', 'overdue', 'paid'])->default('open');
            $t->timestamp('closed_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();

            $t->unique(['card_id', 'month_ref']);
        });

        Schema::table('transactions', function (Blueprint $t) {
            $t->unsignedBigInteger('invoice_id')->nullable()->index()->after('card_id');
            // Se quiser FK forte (só ative depois de criar e povoar):
            // $t->foreign('invoice_id')->references('id')->on('card_invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_invoices');
        Schema::table('transactions', function (Blueprint $t) {
            // $t->dropForeign(['invoice_id']);
            $t->dropColumn('invoice_id');
        });
    }
};
