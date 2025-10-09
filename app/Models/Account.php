<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    public const TYPE_CHECKING = 'checking';

    public const TYPE_WALLET = 'wallet';

    public const TYPE_CARD = 'card';

    public const TYPE_INVESTMENT = 'investment';

    public const TYPE_LOAN = 'loan';

    protected $fillable = [
        'client_id', 'name', 'type', 'on_budget', 'opening_balance', 'currency',
    ];

    protected $casts = [
        'on_budget' => 'boolean',
        'opening_balance' => 'decimal:2',
    ];

    // ---- Relations
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function cardsPaidHere()
    {
        return $this->hasMany(Card::class, 'payment_account_id');
    }
}
