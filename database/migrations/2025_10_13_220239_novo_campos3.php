<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** ========= Helpers ========= */

    /** Drop TODAS as FKs que referenciam uma coluna específica da tabela */
    protected function dropAllForeignKeysForColumn(string $table, string $column): void
    {
        $db = DB::getDatabaseName();

        // FKs da própria tabela que usam essa coluna
        $fks = DB::select(
            'SELECT CONSTRAINT_NAME
               FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME   = ?
                AND COLUMN_NAME  = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$db, $table, $column]
        );

        foreach ($fks as $fk) {
            try {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            } catch (\Throwable $e) {
                // ignora se já tiver sido removida
            }
        }
    }

    /** Verifica se um índice existe */
    protected function indexExists(string $table, string $index): bool
    {
        $rows = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$index]);

        return ! empty($rows);
    }

    /** Retorna todos os nomes de índices que incluem determinada coluna */
    protected function listIndexesThatUseColumn(string $table, string $column): array
    {
        $rows = DB::select('SHOW INDEX FROM `'.$table.'`');
        $byIndex = [];
        foreach ($rows as $r) {
            $idx = $r->Key_name;
            $col = $r->Column_name;
            if (! isset($byIndex[$idx])) {
                $byIndex[$idx] = [];
            }
            $byIndex[$idx][] = $col;
        }
        $hits = [];
        foreach ($byIndex as $idx => $cols) {
            if (in_array($column, $cols, true)) {
                $hits[] = $idx;
            }
        }

        return $hits;
    }

    public function up(): void
    {
        /** ========== 1) Remover parent_id de categories com segurança ========== */
        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'parent_id')) {

            // 1a) Drop TODAS as FKs que usam parent_id
            $this->dropAllForeignKeysForColumn('categories', 'parent_id');

            // 1b) Drop todos os índices que usam parent_id (inclui compostos, como client_id_parent_id)
            $indexes = $this->listIndexesThatUseColumn('categories', 'parent_id');
            foreach ($indexes as $idx) {
                // não dropar a PRIMARY
                if (strtolower($idx) === 'primary') {
                    continue;
                }
                if ($this->indexExists('categories', $idx)) {
                    try {
                        DB::statement("ALTER TABLE `categories` DROP INDEX `{$idx}`");
                    } catch (\Throwable $e) {
                        // pode estar sendo usado por outra FK; mas como removemos as FKs acima, deve seguir
                    }
                }
            }

            // 1c) Remover a coluna
            Schema::table('categories', function (Blueprint $table) {
                if (Schema::hasColumn('categories', 'parent_id')) {
                    $table->dropColumn('parent_id');
                }
            });
        }

        /** ========== 2) Ajustes de FKs de subcategoria nas tabelas de transação ========== */
        // transaction_categories.subcategory_id -> subcategories(id)
        if (Schema::hasTable('transaction_categories') && Schema::hasTable('subcategories')) {
            // drop FK antiga (se existir)
            try {
                DB::statement('ALTER TABLE `transaction_categories` DROP FOREIGN KEY `transaction_categories_subcategory_id_foreign`');
            } catch (\Throwable $e) {
            }
            // cria FK correta
            try {
                DB::statement('ALTER TABLE `transaction_categories`
                    ADD CONSTRAINT `transaction_categories_subcategory_id_foreign`
                    FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories`(`id`) ON DELETE SET NULL');
            } catch (\Throwable $e) {
            }
        }

        // transaction_splits.subcategory_id -> subcategories(id)
        if (Schema::hasTable('transaction_splits') && Schema::hasTable('subcategories')) {
            try {
                DB::statement('ALTER TABLE `transaction_splits` DROP FOREIGN KEY `transaction_splits_subcategory_id_foreign`');
            } catch (\Throwable $e) {
            }
            try {
                DB::statement('ALTER TABLE `transaction_splits`
                    ADD CONSTRAINT `transaction_splits_subcategory_id_foreign`
                    FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories`(`id`) ON DELETE SET NULL');
            } catch (\Throwable $e) {
            }
        }

        /** ========== 3) Índice único novo (opcional) ========== */
        if (Schema::hasTable('categories')) {
            if (! $this->indexExists('categories', 'ux_cat_client_group_name')) {
                try {
                    DB::statement('CREATE UNIQUE INDEX `ux_cat_client_group_name` ON `categories` (COALESCE(`client_id`,0), COALESCE(`group_id`,0), `name`)');
                } catch (\Throwable $e) {
                }
            }
        }
    }

    public function down(): void
    {
        // Recria parent_id (sem recriar FKs antigas)
        if (Schema::hasTable('categories') && ! Schema::hasColumn('categories', 'parent_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unsignedBigInteger('parent_id')->nullable()->index()->after('client_id');
            });
        }

        // Restaurar FKs das subcategorias para categories (estado antigo)
        if (Schema::hasTable('transaction_categories') && Schema::hasTable('categories')) {
            try {
                DB::statement('ALTER TABLE `transaction_categories` DROP FOREIGN KEY `transaction_categories_subcategory_id_foreign`');
            } catch (\Throwable $e) {
            }
            try {
                DB::statement('ALTER TABLE `transaction_categories`
                    ADD CONSTRAINT `transaction_categories_subcategory_id_foreign`
                    FOREIGN KEY (`subcategory_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL');
            } catch (\Throwable $e) {
            }
        }

        if (Schema::hasTable('transaction_splits') && Schema::hasTable('categories')) {
            try {
                DB::statement('ALTER TABLE `transaction_splits` DROP FOREIGN KEY `transaction_splits_subcategory_id_foreign`');
            } catch (\Throwable $e) {
            }
            try {
                DB::statement('ALTER TABLE `transaction_splits`
                    ADD CONSTRAINT `transaction_splits_subcategory_id_foreign`
                    FOREIGN KEY (`subcategory_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL');
            } catch (\Throwable $e) {
            }
        }

        // Remover índice único novo
        if (Schema::hasTable('categories') && $this->indexExists('categories', 'ux_cat_client_group_name')) {
            try {
                DB::statement('ALTER TABLE `categories` DROP INDEX `ux_cat_client_group_name`');
            } catch (\Throwable $e) {
            }
        }
    }
};
