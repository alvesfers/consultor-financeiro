<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define os comandos agendados da aplicação.
     */
    protected function schedule(Schedule $schedule): void
    {
        // timezone do usuário pode variar; como é multi-usuário, rode várias vezes
        $schedule->command('invoices:rebuild')->dailyAt('01:15');
        $schedule->command('invoices:rebuild')->dailyAt('07:15');
    }

    /**
     * Registra os comandos customizados.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
