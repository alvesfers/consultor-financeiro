<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // USERS (base)
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'consultant', 'client'])->default('client');
            $table->string('timezone', 64)->default('America/Sao_Paulo');
            $table->string('locale', 16)->default('pt_BR');
            $table->boolean('active')->default(true);
        });

        // CONSULTANTS
        Schema::create('consultants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('firm_name')->nullable();
            $table->timestamps();
        });

        // CLIENTS
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('consultant_id')->constrained('consultants')->cascadeOnDelete();
            $table->enum('status', ['ativo', 'pausado', 'encerrado'])->default('ativo');
            $table->timestamps();
            $table->index(['consultant_id', 'status']);
        });

        // ACCOUNTS
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['checking', 'wallet', 'card', 'investment', 'loan']);
            $table->boolean('on_budget')->default(true);
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->string('currency', 3)->default('BRL');
            $table->timestamps();
            $table->index(['client_id', 'type']);
        });

        // CARDS
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('name');
            $table->string('brand', 32)->nullable();
            $table->decimal('limit_amount', 18, 2)->nullable();
            $table->unsignedTinyInteger('close_day'); // 1..31
            $table->unsignedTinyInteger('due_day');   // 1..31
            $table->foreignId('payment_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamps();
            $table->index(['client_id', 'due_day']);
        });

        // CATEGORIES (hierarquia categoria/subcategoria)
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['parent_id', 'is_active']);
        });

        // TRANSACTIONS
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();

            // uma transação pode ser em conta (débito/crédito à vista) OU em cartão (lança na fatura)
            $table->foreignId('account_id')->nullable()->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('card_id')->nullable()->constrained('cards')->cascadeOnDelete();

            $table->dateTime('date'); // data/competência da transação
            $table->decimal('amount', 18, 2); // positivo=entrada, negativo=saída
            $table->enum('status', ['pending', 'confirmed', 'reconciled'])->default('confirmed');
            $table->string('method', 32)->nullable(); // pix, boleto, debito, credito, TED, etc
            $table->text('notes')->nullable();

            // Para MySQL 8+: garantir que pelo menos um dos campos (account_id ou card_id) esteja presente
            $table->timestamps();
            $table->index(['client_id', 'date']);
        });

        // CHECK constraint (um ou outro) — só aplica se o SGBD suportar
        try {
            DB::statement('ALTER TABLE transactions
                ADD CONSTRAINT chk_transactions_account_or_card
                CHECK (
                    (account_id IS NOT NULL AND card_id IS NULL)
                    OR (account_id IS NULL AND card_id IS NOT NULL)
                )');
        } catch (\Throwable $e) {
            // ignora se o MySQL da máquina não suportar CHECK
        }

        // TRANSACTION_SPLITS (opcional) — permite ratear uma transação em múltiplas categorias
        Schema::create('transaction_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();     // categoria principal
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->nullOnDelete();  // se usar hierarquia explícita
            $table->decimal('amount', 18, 2); // pode ser negativo
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // TRANSACTION_CATEGORIES (alternativa simples sem split; mantém relacionamento 1-1 com opção de subcategoria)
        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->unique()->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->timestamps();
        });

        // BUDGETS (orçamento mensal por categoria)
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->char('month', 7); // YYYY-MM
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->decimal('planned_amount', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['client_id', 'month', 'category_id']);
            $table->index(['client_id', 'month']);
        });

        // GOALS (objetivos)
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('title');
            $table->decimal('target_amount', 18, 2)->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedTinyInteger('priority')->default(3); // 1..5 (1=alta)
            $table->enum('status', ['ativo', 'pausado', 'concluido', 'atrasado'])->default('ativo');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['client_id', 'status']);
        });

        // GOAL PROGRESS EVENTS
        Schema::create('goal_progress_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->dateTime('date');
            $table->decimal('amount', 18, 2); // aporte positivo, resgate negativo
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['goal_id', 'date']);
        });

        // TASKS
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();  // quem criou (consultor ou cliente)
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete(); // geralmente o cliente

            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('type', ['binary', 'progress', 'habit', 'checklist'])->default('binary');
            $table->enum('frequency', ['once', 'daily', 'weekly', 'monthly', 'yearly', 'custom_rrule'])->default('once');
            $table->string('custom_rrule')->nullable();

            $table->dateTime('start_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->unsignedInteger('remind_before_minutes')->nullable();

            $table->enum('status', ['open', 'done', 'skipped', 'blocked', 'archived'])->default('open');
            $table->enum('visibility', ['client_and_consultant', 'consultant_only', 'client_only'])->default('client_and_consultant');
            $table->boolean('evidence_required')->default(false);

            $table->foreignId('related_goal_id')->nullable()->constrained('goals')->nullOnDelete();
            $table->json('related_entity')->nullable(); // {type: 'account|card|category', id: ...}

            $table->timestamps();
            $table->index(['client_id', 'status', 'due_at']);
        });

        // TASK CHECKLIST ITEMS
        Schema::create('task_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('label');
            $table->boolean('done')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        // TASK UPDATES (histórico / auditoria)
        Schema::create('task_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('updated_by')->constrained('users')->cascadeOnDelete();

            $table->enum('status_new', ['open', 'done', 'skipped', 'blocked', 'archived'])->nullable();
            $table->unsignedTinyInteger('progress_percent')->nullable(); // para tasks de progresso
            $table->text('comment')->nullable();
            $table->string('evidence_file_path')->nullable(); // pode também usar attachments

            $table->timestamps();
            $table->index(['task_id', 'created_at']);
        });

        // PLAYBOOKS
        Schema::create('playbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_id')->constrained('consultants')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // PLAYBOOK TASKS (modelos)
        Schema::create('playbook_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playbook_id')->constrained('playbooks')->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('type', ['binary', 'progress', 'habit', 'checklist'])->default('binary');
            $table->enum('frequency', ['once', 'daily', 'weekly', 'monthly', 'yearly', 'custom_rrule'])->default('once');
            $table->string('custom_rrule')->nullable();
            $table->integer('offset_days_from_start')->default(0);
            $table->unsignedTinyInteger('default_due_hour')->nullable(); // 0..23

            $table->timestamps();
        });

        // NUDGES (lembretes enviados)
        Schema::create('nudges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->enum('channel', ['in_app', 'email', 'whatsapp', 'sms'])->default('in_app');
            $table->enum('sent_by', ['auto', 'consultant'])->default('auto');
            $table->dateTime('sent_at')->nullable();
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->timestamps();
            $table->index(['task_id', 'channel', 'status']);
        });

        // ATTACHMENTS (polimórfico simples)
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type');  // ex: App\Models\Task, App\Models\TaskUpdate, etc
            $table->unsignedBigInteger('owner_id');
            $table->string('path');
            $table->string('mime', 128)->nullable();
            $table->unsignedBigInteger('size')->nullable(); // bytes
            $table->timestamps();
            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        // drop na ordem inversa de dependências
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('nudges');
        Schema::dropIfExists('playbook_tasks');
        Schema::dropIfExists('playbooks');
        Schema::dropIfExists('task_updates');
        Schema::dropIfExists('task_checklist_items');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('goal_progress_events');
        Schema::dropIfExists('goals');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('transaction_categories');
        Schema::dropIfExists('transaction_splits');

        // remover constraint antes (se criada)
        try {
            DB::statement('ALTER TABLE transactions DROP CONSTRAINT chk_transactions_account_or_card');
        } catch (\Throwable $e) {
        }

        Schema::dropIfExists('transactions');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('cards');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('consultants');
        Schema::dropIfExists('users');
    }
};
