<?php

// database/migrations/2025_10_15_120000_create_banks_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // Ex.: Nubank
            $table->string('slug')->unique();       // ex.: nubank
            $table->string('code')->nullable();     // COMPE/ISPB opcional
            $table->string('logo_svg')->nullable(); // caminho: banks/nubank.svg
            $table->string('color_primary', 9)->nullable();   // #7F3DFF
            $table->string('color_secondary', 9)->nullable(); // #...
            $table->string('color_bg', 9)->nullable();        // #...
            $table->string('color_text', 9)->nullable();      // #...
            $table->timestamps();
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_id');
        });
        Schema::dropIfExists('banks');
    }
};
