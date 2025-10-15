<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('category_goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('category_id');
            // usamos o 1º dia do mês para representar o período
            $table->date('month');
            $table->decimal('limit_amount', 12, 2);
            $table->unsignedBigInteger('created_by')->nullable(); // consultor que definiu
            $table->timestamps();

            $table->unique(['client_id', 'category_id', 'month'], 'uniq_cat_goal');

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_goals');
    }
};
