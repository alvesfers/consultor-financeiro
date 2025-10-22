<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecurringRulePosting extends Model
{
    protected $table = 'recurring_rule_postings';

    protected $fillable = [
        'rule_id',
        'month',
        'posted_transaction_id',
        'status',
        'created_by',
    ];

    public function rule()
    {
        return $this->belongsTo(RecurringRule::class, 'rule_id');
    }
}
