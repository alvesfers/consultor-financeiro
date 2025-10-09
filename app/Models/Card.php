<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'name', 'brand', 'limit_amount', 'close_day', 'due_day', 'payment_account_id',
    ];

    protected $casts = [
        'limit_amount' => 'decimal:2',
        'close_day' => 'integer',
        'due_day' => 'integer',
    ];

    // ---- Relations
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function paymentAccount()
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
