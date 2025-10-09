<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id', 'updated_by', 'status_new', 'progress_percent', 'comment', 'evidence_file_path',
    ];

    protected $casts = [
        'progress_percent' => 'integer',
    ];

    // ---- Relations
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'owner');
    }
}
