<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Grava (ou reaproveita) um nó na tabela categories respeitando a unique (client_id, parent_id, name)
     */
    private function upsert(string $name, ?int $parentId = null, ?int $clientId = null): Category
    {
        return Category::firstOrCreate(
            ['client_id' => $clientId, 'parent_id' => $parentId, 'name' => $name],
            ['is_active' => true]
        );
    }

    public function run(): void
    {
        DB::transaction(function () {
            $clientId = null; // global

            // ---------- 1) RECEITA ----------
            $receita = $this->upsert('Receita', null, $clientId);

            // 1.1) Recebimento
            $recebimento = $this->upsert('Recebimento', $receita->id, $clientId);
            foreach ([
                'Dinheiro',
                'Rec. Entre Contas',
                'Recebimento',
                'Reembolso',
                'Remuneração',
                'Renda Extra',
                'Rendimentos',
                'Salário',
                'VR',
            ] as $sub) {
                $this->upsert($sub, $recebimento->id, $clientId);
            }

            // 1.2) Outros (conforme planilha de "Receitas")
            $outrosReceita = $this->upsert('Outros', $receita->id, $clientId);
            foreach (['Saldo', 'Saque'] as $sub) {
                $this->upsert($sub, $outrosReceita->id, $clientId);
            }

            // ---------- 2) SALDO ----------
            $saldo = $this->upsert('Saldo', null, $clientId);
            $this->upsert('Saldo', $saldo->id, $clientId); // categoria "Saldo" com sub "Saldo" simples

            // ---------- 3) SAQUE ----------
            $saque = $this->upsert('Saque', null, $clientId);
            $this->upsert('Saque', $saque->id, $clientId); // categoria "Saque" com sub "Saque" simples

            // ---------- 4) PRODUTOS (INVESTIMENTOS) ----------
            $produtos = $this->upsert('Produtos', null, $clientId);

            // 4.1) Investimento (aportes/alocações)
            $invest = $this->upsert('Investimento', $produtos->id, $clientId);
            foreach ([
                'Ações',
                'CDB',
                'COE',
                'Criptomoeda',
                'Exterior',
                'FII',
                'LCA',
                'LCI',
                'Poupança',
                'Previd. Privada',
                'Reserva Financeira',
                'Tesouro Direto',
                'Tesouro Selic',
            ] as $sub) {
                $this->upsert($sub, $invest->id, $clientId);
            }

            // 4.2) Resgate (saídas vindas de produtos)
            $resgate = $this->upsert('Resgate', $produtos->id, $clientId);
            foreach ([
                'Resg. Ações',
                'Resg. CDB',
                'Resg. COE',
                'Resg. Criptomoeda',
                'Resg. Exterior',
                'Resg. FII',
                'Resg. LCA',
                'Resg. LCI',
                'Resg. Poupança',
                'Resg. Previd. Privada',
                'Resg. Reserva Financeira',
                'Resg. Tesouro Direto',
                'Resg. Tesouro Selic',
            ] as $sub) {
                $this->upsert($sub, $resgate->id, $clientId);
            }

            // ---------- 5) DESPESAS ----------
            $despesas = $this->upsert('Despesas', null, $clientId);

            // 5.1) Alimentação
            $cat = $this->upsert('Alimentação', $despesas->id, $clientId);
            foreach (['Lanche', 'Padaria', 'Restaurante', 'Supermercado', 'Suplementos', 'Marmitas'] as $sub) {
                $this->upsert($sub, $cat->id, $clientId);
            }

            // 5.2) Educação
            $cat = $this->upsert('Educação', $despesas->id, $clientId);
            foreach ([
                'Consultoria Financeira', 'Curso', 'Escola', 'Faculdade', 'Idiomas',
                'Livros', 'Papelaria', 'Pós Graduação',
            ] as $sub) {
                $this->upsert($sub, $cat->id, $clientId);
            }

            // 5.3) Governo (conforme sua planilha)
            $cat = $this->upsert('Governo', $despesas->id, $clientId);
            foreach ([
                'Aplicativo', 'Cemig', 'Conselho Classe', 'Copasa', 'Escritura', 'Gás',
                'Internet', 'IPTU', 'Netflix', 'Telefones', 'TV', 'Amazon Prime',
                'Spotify', 'Youtube', 'Chat GPT', 'Github', 'Google',
            ] as $sub) {
                $this->upsert($sub, $cat->id, $clientId);
            }

            // 5.4) Habitação
            $cat = $this->upsert('Habitação', $despesas->id, $clientId);
            foreach ([
                'Aces. Telefone', 'Alarmes', 'Aluguel', 'Compra Imóvel', 'Condomínio',
                'Eletricista', 'Faxineira', 'Jardinagem', 'Lavanderia', 'Móveis', 'Pet',
                'Prestação Imóvel', 'Reformas/Consertos/Copias', 'Segurança', 'Utensílios',
                'Passar Roupas',
            ] as $sub) {
                $this->upsert($sub, $cat->id, $clientId);
            }

            // 5.5) Impostos / Encargos
            $cat = $this->upsert('Impostos', $despesas->id, $clientId);
            foreach ([
                'Anuidade', 'Emprestimos', 'Encargos', 'IOF', 'Jogos', 'Juros', 'Multa',
                'Seguro Cartão', 'Tarifa', 'Emp. Santander', 'Emp. Itau', 'Emp. Bradesco',
            ] as $sub) {
                $this->upsert($sub, $cat->id, $clientId);
            }

            // 5.6) Lazer
            $cat = $this->upsert('Lazer', $despesas->id, $clientId);
            foreach ([
                'Academia', 'Clube', 'Crossfit', 'Diversão', 'Dízimo', 'Esporte', 'Festas',
                'Gympass', 'Hotel', 'Livraria/Jornal', 'Personal', 'Presentes', 'Viagens',
            ] as $sub) {
                $this->upsert($sub, $cat->id, $clientId);
            }

            // 5.7) Saúde
            $cat = $this->upsert('Saúde', $despesas->id, $clientId);
            foreach ([
                'Barbearia', 'Cílios', 'Consulta', 'Cosméticos', 'Dentista', 'Depilação',
                'Estética', 'Exames', 'Farmácia', 'Higiene Pessoal', 'Manicure', 'Massagem',
                'Nutricionista', 'Plano Saúde', 'Psicologa', 'Psiquiatra', 'Salão Beleza',
                'Sobrancelha',
            ] as $sub) {
                $this->upsert($sub, $cat->id, $clientId);
            }

            // 5.8) Veículo
            $cat = $this->upsert('Veículo', $despesas->id, $clientId);
            foreach ([
                'Aluguel Veiculo', 'CNH', 'Compra Veiculo', 'Documentos', 'DPVAT',
                'Estacionamento', 'Gasolina', 'IPVA', 'Lavagens', 'Licenciamento',
                'Manutenção', 'Moto Táxi', 'Multas', 'Ônibus / Metro', 'Pedagio',
                'Prestação Veiculo', 'Revisão', 'Seguro Veiculo', 'Uber',
            ] as $sub) {
                $this->upsert($sub, $cat->id, $clientId);
            }

            // 5.9) Vestuário
            $cat = $this->upsert('Vestuário', $despesas->id, $clientId);
            foreach (['Acessórios', 'Bijuteria', 'Bolsa', 'Calçados', 'Cama Mesa Banho', 'Perfume', 'Roupas'] as $sub) {
                $this->upsert($sub, $cat->id, $clientId);
            }

            // 5.10) Outros
            $cat = $this->upsert('Outros', $despesas->id, $clientId);
            foreach ([
                'Checar', 'Desp. Entre Contas', 'Desp. Terceiros', 'Doação', 'Fatura Cartão',
                'Verificar', 'Desp. Mãe', 'Desp. Filho', 'Desp. Escritório', 'Desp. Pensão',
            ] as $sub) {
                $this->upsert($sub, $cat->id, $clientId);
            }
        });
    }
}
