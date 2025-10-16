<?php

// app/Console/Commands/RebuildCardInvoices.php

namespace App\Console\Commands;

use App\Services\CardInvoiceService;
use Illuminate\Console\Command;

class RebuildCardInvoices extends Command
{
    protected $signature = 'invoices:rebuild {client_id?}';

    protected $description = 'Recria/fecha faturas a partir de transactions';

    public function handle(CardInvoiceService $svc)
    {
        if ($cid = $this->argument('client_id')) {
            $svc->rebuildForClient((int) $cid);
        } else {
            // todos clientes
            $clients = \DB::table('clients')->pluck('id');
            foreach ($clients as $clientId) {
                $svc->rebuildForClient((int) $clientId);
            }
        }
        $this->info('Ok');
    }
}
