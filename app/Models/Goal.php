<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
