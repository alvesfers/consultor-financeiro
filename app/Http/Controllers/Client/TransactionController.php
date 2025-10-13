<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function store(Request $request, $consultant)
    {
        return app(\App\Http\Controllers\ClientTransactionController::class)->store($request, $consultant);
    }
}
