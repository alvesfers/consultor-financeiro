<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="lofi">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ config('app.name', 'Consultoria Financeira') }}</title>

    {{-- Fonts (opcional) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    {{-- Vite assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
        }

        html,
        body {
            font-family: var(--font-sans);
        }
    </style>
</head>

<body class="min-h-screen bg-base-100 text-base-content">
    {{-- NAVBAR --}}
    <header class="border-b border-base-200">
        <div class="navbar max-w-7xl mx-auto px-4">
            <div class="navbar-start">
                <a href="{{ url('/') }}" class="flex items-center gap-2">
                    <div class="avatar placeholder">
                        <div class="bg-primary text-primary-content w-10 rounded-lg">
                            <span class="text-lg font-bold">CF</span>
                        </div>
                    </div>
                    <span class="text-lg font-semibold">{{ config('app.name', 'Consultoria Financeira') }}</span>
                </a>
            </div>

            <div class="navbar-center hidden md:flex">
                <ul class="menu menu-horizontal gap-2">
                    <li><a href="#beneficios">Benefícios</a></li>
                    <li><a href="#como-funciona">Como funciona</a></li>
                    <li><a href="#recursos">Recursos</a></li>
                    <li><a href="#precos">Planos</a></li>
                </ul>
            </div>

            <div class="navbar-end gap-2">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-ghost">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-ghost">Entrar</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-primary">Começar agora</a>
                        @endif
                    @endauth
                @else
                    <a href="#" class="btn btn-primary">Começar agora</a>
                @endif
            </div>
        </div>
    </header>

    {{-- HERO --}}
    <section class="relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 py-16 md:py-24 grid md:grid-cols-2 gap-10 items-center">
            <div>
                <div class="badge badge-primary badge-lg mb-4">Consultoria financeira moderna</div>
                <h1 class="text-4xl md:text-5xl font-extrabold leading-tight">
                    Organize, acompanhe e faça seus clientes
                    <span class="text-primary">prosperarem</span>
                </h1>
                <p class="mt-4 text-base md:text-lg opacity-80">
                    Uma plataforma onde o consultor conduz e o cliente executa.
                    Troque planilhas por um fluxo claro de tarefas, objetivos, orçamentos e investimentos —
                    tudo em um único lugar.
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Criar minha conta</a>
                    @endif
                    <a href="#como-funciona" class="btn btn-ghost btn-lg">Ver como funciona</a>
                </div>

                {{-- Trust indicators --}}
                <div class="mt-8 grid grid-cols-3 gap-4 text-sm opacity-80 max-w-md">
                    <div class="flex items-center gap-2">
                        <span class="badge badge-outline">Open Finance-ready</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="badge badge-outline">Criptografia em trânsito</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="badge badge-outline">Controle de acesso</span>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="mockup-window border bg-base-200">
                    <div class="px-6 py-6 bg-base-100">
                        {{-- Mock do dashboard do consultor --}}
                        <div class="grid md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <div class="card bg-base-100 border border-base-200">
                                    <div class="card-body">
                                        <div class="flex items-center justify-between">
                                            <h3 class="card-title text-base">Clientes com risco (este mês)</h3>
                                            <div class="badge badge-warning">5</div>
                                        </div>
                                        <div class="stats stats-vertical lg:stats-horizontal shadow mt-4">
                                            <div class="stat">
                                                <div class="stat-title">Conclusão de tasks</div>
                                                <div class="stat-value">82%</div>
                                                <div class="stat-desc">+6% vs mês passado</div>
                                            </div>
                                            <div class="stat">
                                                <div class="stat-title">Objetivos em risco</div>
                                                <div class="stat-value">7</div>
                                                <div class="stat-desc">-2 desde a última revisão</div>
                                            </div>
                                            <div class="stat">
                                                <div class="stat-title">Faturas a vencer</div>
                                                <div class="stat-value">12</div>
                                                <div class="stat-desc">em 5 dias</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card bg-base-100 border border-base-200 mt-4">
                                    <div class="card-body">
                                        <h3 class="card-title text-base">Tarefas da semana</h3>
                                        <ul class="mt-2 space-y-2">
                                            <li
                                                class="flex items-center justify-between p-3 rounded-box border border-base-200">
                                                <div class="flex items-center gap-3">
                                                    <input type="checkbox" class="checkbox checkbox-primary" checked />
                                                    <span>Classificar transações pendentes (Cliente Ana)</span>
                                                </div>
                                                <span class="badge">Hoje</span>
                                            </li>
                                            <li
                                                class="flex items-center justify-between p-3 rounded-box border border-base-200">
                                                <div class="flex items-center gap-3">
                                                    <input type="checkbox" class="checkbox checkbox-primary" />
                                                    <span>Negociar anuidade do cartão (Cliente Marcos)</span>
                                                </div>
                                                <span class="badge badge-outline">Amanhã</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="card bg-base-100 border border-base-200">
                                    <div class="card-body">
                                        <h3 class="card-title text-base">Objetivos</h3>
                                        <div class="space-y-3">
                                            <div>
                                                <div class="flex justify-between text-sm mb-1">
                                                    <span>Reserva de emergência</span>
                                                    <span class="opacity-70">60%</span>
                                                </div>
                                                <progress class="progress progress-primary" value="60"
                                                    max="100"></progress>
                                            </div>
                                            <div>
                                                <div class="flex justify-between text-sm mb-1">
                                                    <span>Quitar cartão Z</span>
                                                    <span class="opacity-70">35%</span>
                                                </div>
                                                <progress class="progress progress-secondary" value="35"
                                                    max="100"></progress>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card bg-base-100 border border-base-200">
                                    <div class="card-body">
                                        <h3 class="card-title text-base">Alertas</h3>
                                        <div class="alert alert-warning">
                                            <span>Gasto atípico em Alimentação (Cliente João)</span>
                                        </div>
                                        <div class="alert alert-info">
                                            <span>Fatura do cartão X fecha em 2 dias (Cliente Bruna)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                {{-- Glow decorativo --}}
                <div
                    class="pointer-events-none absolute -inset-8 blur-3xl opacity-20 bg-gradient-to-tr from-primary to-secondary rounded-3xl">
                </div>
            </div>
        </div>
    </section>

    {{-- BENEFÍCIOS --}}
    <section id="beneficios" class="py-16 md:py-24 border-t border-base-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center max-w-2xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-bold">Menos planilhas, mais resultado</h2>
                <p class="mt-3 opacity-80">Centralize o financeiro do cliente, conduza tarefas de curto prazo e
                    acompanhe metas de longo prazo.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-6 mt-10">
                <div class="card bg-base-100 border border-base-200">
                    <div class="card-body">
                        <div class="badge badge-primary w-fit mb-2">Fluxo guiado</div>
                        <h3 class="card-title">Tasks & Playbooks</h3>
                        <p>Crie rotinas e checklists que o cliente executa. Evidências, comentários e lembretes
                            inclusos.</p>
                    </div>
                </div>
                <div class="card bg-base-100 border border-base-200">
                    <div class="card-body">
                        <div class="badge badge-secondary w-fit mb-2">Visão 360°</div>
                        <h3 class="card-title">Contas, cartões e investimentos</h3>
                        <p>Controle faturas, parcelamentos e o que é on/off budget. Veja o patrimônio evoluir ao longo
                            do tempo.</p>
                    </div>
                </div>
                <div class="card bg-base-100 border border-base-200">
                    <div class="card-body">
                        <div class="badge badge-accent w-fit mb-2">Insights</div>
                        <h3 class="card-title">Orçamento & alertas</h3>
                        <p>Planejado vs. realizado por categoria, alertas de estouro e gastos atípicos.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- COMO FUNCIONA --}}
    <section id="como-funciona" class="py-16 md:py-24 border-t border-base-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center max-w-2xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-bold">Como funciona</h2>
                <p class="mt-3 opacity-80">Do onboarding à revisão mensal — simples e auditável.</p>
            </div>

            <ul class="steps steps-vertical md:steps-horizontal w-full mt-10">
                <li class="step step-primary">Onboarding do cliente</li>
                <li class="step step-primary">Aplicar playbook</li>
                <li class="step step-primary">Cliente executa tasks</li>
                <li class="step">Revisão mensal & próximos passos</li>
            </ul>

            <div class="grid md:grid-cols-2 gap-6 mt-10">
                <div class="collapse collapse-arrow bg-base-100 border border-base-200">
                    <input type="checkbox" />
                    <div class="collapse-title text-lg font-medium">Tarefas de curto período (binária, progresso,
                        hábito, checklist)</div>
                    <div class="collapse-content opacity-80">
                        <p>Foque no que importa: o cliente marca feito/skip/bloqueado, anexa evidência e você acompanha
                            em tempo real.</p>
                    </div>
                </div>
                <div class="collapse collapse-arrow bg-base-100 border border-base-200">
                    <input type="checkbox" />
                    <div class="collapse-title text-lg font-medium">Objetivos e metas com prazo</div>
                    <div class="collapse-content opacity-80">
                        <p>Metas como “reserva de emergência” ou “quitar cartão”. Progresso por aporte ou checklist.</p>
                    </div>
                </div>
                <div class="collapse collapse-arrow bg-base-100 border border-base-200">
                    <input type="checkbox" />
                    <div class="collapse-title text-lg font-medium">Cartões com fechamento & vencimento</div>
                    <div class="collapse-content opacity-80">
                        <p>Compras entram na fatura correta. Pagamento baixa no caixa. Parcelas distribuídas por
                            competência.</p>
                    </div>
                </div>
                <div class="collapse collapse-arrow bg-base-100 border border-base-200">
                    <input type="checkbox" />
                    <div class="collapse-title text-lg font-medium">Categorias e subcategorias</div>
                    <div class="collapse-content opacity-80">
                        <p>Habitação → Água/Luz/Aluguel; Alimentação → Mercado/Restaurante; e tags livres para
                            relatórios poderosos.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- RECURSOS EM DESTAQUE --}}
    <section id="recursos" class="py-16 md:py-24 border-t border-base-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center max-w-2xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-bold">Feito para consultores</h2>
                <p class="mt-3 opacity-80">Ferramentas que escalam o seu trabalho sem perder o toque humano.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6 mt-10">
                <div class="card bg-base-100 border border-base-200 h-full">
                    <div class="card-body">
                        <h3 class="card-title">Painel 360 do consultor</h3>
                        <p class="opacity-80">Semáforos de risco, faturas próximas do vencimento, aderência às tarefas
                            e metas atrasadas.</p>
                        <div class="mt-4">
                            <span class="badge badge-outline">Acompanhamento semanal</span>
                            <span class="badge badge-outline ml-2">Alertas automáticos</span>
                        </div>
                    </div>
                </div>
                <div class="card bg-base-100 border border-base-200 h-full">
                    <div class="card-body">
                        <h3 class="card-title">Relatórios e evidências</h3>
                        <p class="opacity-80">Linha do tempo por task e cliente. PDFs e prints anexados para auditoria
                            simples.</p>
                        <div class="mt-4">
                            <span class="badge badge-outline">Auditoria</span>
                            <span class="badge badge-outline ml-2">Histórico</span>
                        </div>
                    </div>
                </div>
                <div class="card bg-base-100 border border-base-200 h-full">
                    <div class="card-body">
                        <h3 class="card-title">Orçamento por categoria</h3>
                        <p class="opacity-80">Planejado vs. realizado com média móvel. Envelopes e desvios por período.
                        </p>
                        <div class="mt-4">
                            <span class="badge badge-outline">Planejamento</span>
                            <span class="badge badge-outline ml-2">Comparativos</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Depoimentos / prova social --}}
            <div class="mt-12 grid md:grid-cols-3 gap-6">
                <div class="card bg-base-100 border border-base-200">
                    <div class="card-body">
                        <div class="rating rating-sm mb-2">
                            <input type="radio" class="mask mask-star-2 bg-warning" checked />
                            <input type="radio" class="mask mask-star-2 bg-warning" checked />
                            <input type="radio" class="mask mask-star-2 bg-warning" checked />
                            <input type="radio" class="mask mask-star-2 bg-warning" checked />
                            <input type="radio" class="mask mask-star-2 bg-warning" checked />
                        </div>
                        <p class="opacity-80">“Troquei todas as minhas planilhas por esta plataforma. O cliente entende
                            o que fazer e eu acompanho sem fricção.”</p>
                        <div class="mt-4 flex items-center gap-3">
                            <div class="avatar placeholder">
                                <div class="bg-neutral text-neutral-content w-10 rounded-full">A</div>
                            </div>
                            <div>
                                <p class="font-medium">Aline — Consultora</p>
                                <p class="text-sm opacity-60">Planejamento Pessoal</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card bg-base-100 border border-base-200">
                    <div class="card-body">
                        <div class="rating rating-sm mb-2">
                            <input type="radio" class="mask mask-star-2 bg-warning" checked />
                            <input type="radio" class="mask mask-star-2 bg-warning" checked />
                            <input type="radio" class="mask mask-star-2 bg-warning" checked />
                            <input type="radio" class="mask mask-star-2 bg-warning" checked />
                            <input type="radio" class="mask mask-star-2 bg-warning" />
                        </div>
                        <p class="opacity-80">“O cliente marca evidência no celular e eu aprovo. Minha revisão mensal
                            ficou 2x mais rápida.”</p>
                        <div class="mt-4 flex items-center gap-3">
                            <div class="avatar placeholder">
                                <div class="bg-neutral text-neutral-content w-10 rounded-full">M</div>
                            </div>
                            <div>
                                <p class="font-medium">Marcos — Consultor</p>
                                <p class="text-sm opacity-60">Finanças do Dia a Dia</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card bg-base-100 border border-base-200">
                    <div class="card-body">
                        <div class="rating rating-sm mb-2">
                            <input type="radio" class="mask mask-star-2 bg-warning" checked />
                            <input type="radio" class="mask mask-star-2 bg-warning" checked />
                            <input type="radio" class="mask mask-star-2 bg-warning" checked />
                            <input type="radio" class="mask mask-star-2 bg-warning" checked />
                            <input type="radio" class="mask mask-star-2 bg-warning" checked />
                        </div>
                        <p class="opacity-80">“Playbooks prontos para onboarding e metas. Profissionalizou meu
                            atendimento.”</p>
                        <div class="mt-4 flex items-center gap-3">
                            <div class="avatar placeholder">
                                <div class="bg-neutral text-neutral-content w-10 rounded-full">J</div>
                            </div>
                            <div>
                                <p class="font-medium">Juliana — Consultora</p>
                                <p class="text-sm opacity-60">Orçamento Familiar</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- PREÇOS / CTA --}}
    <section id="precos" class="py-16 md:py-24 border-t border-base-200 bg-base-200/40">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center max-w-2xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-bold">Planos que crescem com você</h2>
                <p class="mt-3 opacity-80">Comece simples e escale o número de clientes com automações.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6 mt-10">
                <div class="card bg-base-100 border border-base-200">
                    <div class="card-body">
                        <h3 class="card-title">Start</h3>
                        <p class="opacity-80">Para começar com seus primeiros clientes.</p>
                        <div class="mt-2 text-3xl font-extrabold">R$ 0</div>
                        <ul class="mt-4 space-y-2 text-sm opacity-80">
                            <li>• 5 clientes</li>
                            <li>• Tasks & objetivos</li>
                            <li>• Exportação CSV</li>
                        </ul>
                        <div class="card-actions mt-6">
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-primary w-full">Começar grátis</a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card bg-base-100 border border-base-200 outline outline-2 outline-primary">
                    <div class="card-body">
                        <div class="badge badge-primary w-fit">Mais popular</div>
                        <h3 class="card-title mt-1">Pro</h3>
                        <p class="opacity-80">Para consultores ativos com carteira em crescimento.</p>
                        <div class="mt-2 text-3xl font-extrabold">R$ 79/mês</div>
                        <ul class="mt-4 space-y-2 text-sm opacity-80">
                            <li>• 50 clientes</li>
                            <li>• Playbooks & lembretes</li>
                            <li>• Relatórios mensais</li>
                        </ul>
                        <div class="card-actions mt-6">
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-primary w-full">Assinar Pro</a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card bg-base-100 border border-base-200">
                    <div class="card-body">
                        <h3 class="card-title">Enterprise</h3>
                        <p class="opacity-80">Times, permissões avançadas e integrações.</p>
                        <div class="mt-2 text-3xl font-extrabold">Sob consulta</div>
                        <ul class="mt-4 space-y-2 text-sm opacity-80">
                            <li>• Clientes ilimitados</li>
                            <li>• SSO & auditoria</li>
                            <li>• SLA & suporte dedicado</li>
                        </ul>
                        <div class="card-actions mt-6">
                            <a href="#contato" class="btn btn-ghost w-full">Falar com vendas</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CTA final --}}
            <div class="mt-14 text-center">
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Quero experimentar agora</a>
                @endif
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-16 md:py-24 border-t border-base-200">
        <div class="max-w-5xl mx-auto px-4">
            <div class="text-center max-w-2xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-bold">Perguntas frequentes</h2>
                <p class="mt-3 opacity-80">Tudo o que você precisa para começar com confiança.</p>
            </div>

            <div class="mt-10 space-y-3">
                <div class="collapse collapse-plus bg-base-100 border border-base-200">
                    <input type="checkbox" />
                    <div class="collapse-title text-lg font-medium">Posso cadastrar dados do cliente eu mesmo?</div>
                    <div class="collapse-content opacity-80">
                        <p>Sim. O consultor pode criar contas, cartões, metas e tasks para o cliente — inclusive como
                            rascunho privado e publicar depois.</p>
                    </div>
                </div>
                <div class="collapse collapse-plus bg-base-100 border border-base-200">
                    <input type="checkbox" />
                    <div class="collapse-title text-lg font-medium">Como funcionam as faturas de cartão?</div>
                    <div class="collapse-content opacity-80">
                        <p>As compras entram na fatura do período correto; no pagamento, o caixa é atualizado. Parcelas
                            são distribuídas por competência.</p>
                    </div>
                </div>
                <div class="collapse collapse-plus bg-base-100 border border-base-200">
                    <input type="checkbox" />
                    <div class="collapse-title text-lg font-medium">Dá para usar orçamento por categoria?</div>
                    <div class="collapse-content opacity-80">
                        <p>Sim. Planejado vs. realizado por categoria, com alertas de estouro e comparativo com média
                            móvel.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="border-t border-base-200">
        <div class="footer max-w-7xl mx-auto px-4 py-12 text-base-content">
            <aside>
                <div class="avatar placeholder">
                    <div class="bg-primary text-primary-content w-10 rounded-lg">
                        <span class="text-lg font-bold">CF</span>
                    </div>
                </div>
                <p>
                    {{ config('app.name', 'Consultoria Financeira') }}<br />
                    Plataforma de consultoria financeira com foco em execução.
                </p>
            </aside>
            <nav>
                <h6 class="footer-title">Produto</h6>
                <a href="#beneficios" class="link link-hover">Benefícios</a>
                <a href="#recursos" class="link link-hover">Recursos</a>
                <a href="#precos" class="link link-hover">Planos</a>
            </nav>
            <nav>
                <h6 class="footer-title">Conta</h6>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="link link-hover">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="link link-hover">Entrar</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="link link-hover">Criar conta</a>
                        @endif
                    @endauth
                @endif
            </nav>
            <nav>
                <h6 class="footer-title">Legal</h6>
                <a class="link link-hover">Termos</a>
                <a class="link link-hover">Privacidade</a>
            </nav>
        </div>
        <div class="text-center text-sm opacity-60 pb-8">
            © {{ date('Y') }} {{ config('app.name', 'Consultoria Financeira') }} — Todos os direitos reservados.
        </div>
    </footer>
</body>

</html>
