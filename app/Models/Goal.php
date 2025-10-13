<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Goal extends Model
{
    use HasFactory;

    public const STATUS_ATIVO = 'ativo';

    public const STATUS_PAUSADO = 'pausado';

    public const STATUS_CONCLUIDO = 'concluido';

    public const STATUS_ATRASADO = 'atrasado';

    protected $fillable = [
        'client_id', 'title', 'target_amount', 'due_date', 'priority', 'status', 'created_by',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'due_date' => 'date',
        'priority' => 'integer',
    ];

    // ---- Relations
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function progressEvents()
    {
        return $this->hasMany(GoalProgressEvent::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'related_goal_id');
    }

    public function scopePending($q)
    {
        if (Schema::hasColumn($this->getTable(), 'achieved_at')) {
            return $q->whereNull('achieved_at');
        }

        if (Schema::hasColumn($this->getTable(), 'is_achieved')) {
            return $q->where('is_achieved', false);
        }

        if (Schema::hasColumn($this->getTable(), 'status')) {
            return $q->where('status', '!=', 'completed');
        }

        return $q;
    }
}
