<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionSplit extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id', 'category_id', 'subcategory_id', 'amount', 'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // ---- Relations
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }
}
