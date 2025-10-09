<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_RECONCILED = 'reconciled';

    protected $fillable = [
        'client_id', 'account_id', 'card_id', 'date', 'amount', 'status', 'method', 'notes',
    ];

    protected $casts = [
        'date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    // ---- Relations
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    public function splits()
    {
        return $this->hasMany(TransactionSplit::class);
    }

    public function categoryLink()
    {
        // relação 1-1 alternativa quando não usar splits
        return $this->hasOne(TransactionCategory::class);
    }
}
