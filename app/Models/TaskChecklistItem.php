<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = ['task_id', 'label', 'done', 'sort'];

    protected $casts = [
        'done' => 'boolean',
        'sort' => 'integer',
    ];

    // ---- Relations
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
