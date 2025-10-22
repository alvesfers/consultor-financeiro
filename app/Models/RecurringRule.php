<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecurringRule extends Model
{
    protected $table = 'recurring_rules';

    protected $fillable = [
        'client_id',
        'name',
        'merchant',
        'account_id',
        'card_id',
        'amount',
        'type',
        'method',
        'category_id',
        'subcategory_id',
        'notes',
        'freq',
        'interval',
        'by_month_day',
        'shift_rule',
        'start_date',
        'end_date',
        'autopay',
        'is_active',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'autopay'      => 'boolean',
        'is_active'    => 'boolean',
        'interval'     => 'integer',
        'by_month_day' => 'integer',
        'start_date'   => 'date',
        'end_date'     => 'date',
    ];

    // relationships (ajuste se os namespaces diferirem)
    public function account()     { return $this->belongsTo(Account::class); }
    public function card()        { return $this->belongsTo(Card::class); }
    public function category()    { return $this->belongsTo(Category::class); }
    public function subcategory() { return $this->belongsTo(Subcategory::class); }
}
