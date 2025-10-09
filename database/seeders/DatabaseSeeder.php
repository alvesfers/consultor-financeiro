<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Card;
use App\Models\Category;
use App\Models\Client;
use App\Models\Consultant;
use App\Models\Task;
use App\Models\TaskChecklistItem;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --------------------------------------------------------
        // ADMIN
        // --------------------------------------------------------
        $admin = User::factory()->admin()->create([
            'name' => 'Admin Master',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        // --------------------------------------------------------
        // CONSULTOR
        // --------------------------------------------------------
        $consultantUser = User::factory()->consultant()->create([
            'name' => 'João Consultor',
            'email' => 'consultor@example.com',
            'password' => Hash::make('password'),
        ]);

        $consultant = Consultant::factory()->create([
            'user_id' => $consultantUser->id,
            'firm_name' => 'JS Consultoria Financeira',
        ]);

        // --------------------------------------------------------
        // CATEGORIAS BÁSICAS (fixas)
        // --------------------------------------------------------
        $categoriasBase = collect([
            'Habitação' => ['Aluguel', 'Condomínio', 'Luz', 'Água'],
            'Transporte' => ['Gasolina', 'Uber', 'Ônibus'],
            'Alimentação' => ['Mercado', 'Restaurante', 'Delivery'],
            'Saúde' => ['Farmácia', 'Plano de saúde'],
        ]);

        $categoriasBase->each(function ($subs, $pai) {
            $catPai = Category::create(['name' => $pai, 'is_active' => true]);
            foreach ($subs as $sub) {
                Category::create([
                    'parent_id' => $catPai->id,
                    'name' => $sub,
                    'is_active' => true,
                ]);
            }
        });

        $categorias = Category::whereNotNull('parent_id')->get();

        // --------------------------------------------------------
        // CLIENTES (2)
        // --------------------------------------------------------
        for ($i = 1; $i <= 2; $i++) {

            // Usuário + cliente
            $user = User::factory()->client()->create([
                'name' => "Cliente {$i}",
                'email' => "cliente{$i}@example.com",
                'password' => Hash::make('password'),
            ]);

            $client = Client::factory()->create([
                'user_id' => $user->id,
                'consultant_id' => $consultant->id,
                'status' => 'ativo',
            ]);

            // Contas
            $contaPrincipal = Account::factory()->for($client)->checking()->create([
                'name' => 'Conta Corrente Principal',
                'opening_balance' => 2500,
            ]);

            $contaInvest = Account::factory()->for($client)->investment()->create([
                'name' => 'Investimentos XP',
                'on_budget' => false,
            ]);

            // Cartão
            $cartao = Card::factory()->for($client)->create([
                'name' => 'Cartão Nubank',
                'limit_amount' => 3000,
                'payment_account_id' => $contaPrincipal->id,
            ]);

            // Transações básicas
            foreach (range(1, 6) as $j) {
                $categoria = $categorias->random();
                $valor = fake()->randomFloat(2, 20, 500);
                $isDespesa = fake()->boolean(80);

                $tx = Transaction::create([
                    'client_id' => $client->id,
                    'account_id' => $contaPrincipal->id,
                    'date' => now()->subDays(rand(0, 20)),
                    'amount' => $isDespesa ? -$valor : $valor,
                    'status' => 'confirmed',
                    'method' => $isDespesa ? 'pix' : 'transfer',
                    'notes' => $isDespesa ? 'Despesa com '.$categoria->name : 'Receita extra',
                ]);

                TransactionCategory::create([
                    'transaction_id' => $tx->id,
                    'category_id' => $categoria->parent_id,
                    'subcategory_id' => $categoria->id,
                ]);
            }

            // Tasks simples
            $task = Task::create([
                'client_id' => $client->id,
                'created_by' => $consultantUser->id,
                'assigned_to' => $user->id,
                'title' => 'Enviar extrato bancário do mês',
                'description' => 'Faça upload do PDF do extrato mensal.',
                'type' => 'checklist',
                'frequency' => 'once',
                'status' => 'open',
                'visibility' => 'client_and_consultant',
                'evidence_required' => true,
                'start_at' => now()->subDays(2),
                'due_at' => now()->addDays(3),
            ]);

            TaskChecklistItem::insert([
                ['task_id' => $task->id, 'label' => 'Baixar extrato em PDF', 'done' => false, 'sort' => 1],
                ['task_id' => $task->id, 'label' => 'Salvar em pasta segura', 'done' => false, 'sort' => 2],
                ['task_id' => $task->id, 'label' => 'Enviar para consultor', 'done' => false, 'sort' => 3],
            ]);
        }

        echo "✅ Seed finalizada: 1 consultor + 2 clientes criados.\n";
        echo "Usuários para teste:\n";
        echo "- admin@example.com / password\n";
        echo "- consultor@example.com / password\n";
        echo "- cliente1@example.com / password\n";
        echo "- cliente2@example.com / password\n";
    }
}
