<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoalProgressEvent extends Model
{
    use HasFactory;

    protected $fillable = ['goal_id', 'date', 'amount', 'note'];

    protected $casts = [
        'date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    // ---- Relations
    public function goal()
    {
        return $this->belongsTo(Goal::class);
    }
}
