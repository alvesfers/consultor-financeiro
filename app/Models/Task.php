<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    public const TYPE_BINARY = 'binary';

    public const TYPE_PROGRESS = 'progress';

    public const TYPE_HABIT = 'habit';

    public const TYPE_CHECKLIST = 'checklist';

    public const FREQ_ONCE = 'once';

    public const FREQ_DAILY = 'daily';

    public const FREQ_WEEKLY = 'weekly';

    public const FREQ_MONTHLY = 'monthly';

    public const FREQ_YEARLY = 'yearly';

    public const FREQ_CUSTOM = 'custom_rrule';

    public const STATUS_OPEN = 'open';

    public const STATUS_DONE = 'done';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_ARCHIVED = 'archived';

    public const VIS_CLIENT_AND_CONSULTANT = 'client_and_consultant';

    public const VIS_CONSULTANT_ONLY = 'consultant_only';

    public const VIS_CLIENT_ONLY = 'client_only';

    protected $fillable = [
        'client_id', 'created_by', 'assigned_to', 'title', 'description',
        'type', 'frequency', 'custom_rrule', 'start_at', 'due_at', 'remind_before_minutes',
        'status', 'visibility', 'evidence_required', 'related_goal_id', 'related_entity',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'due_at' => 'datetime',
        'remind_before_minutes' => 'integer',
        'evidence_required' => 'boolean',
        'related_entity' => 'array',
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

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function goal()
    {
        return $this->belongsTo(Goal::class, 'related_goal_id');
    }

    public function checklistItems()
    {
        return $this->hasMany(TaskChecklistItem::class)->orderBy('sort');
    }

    public function updates()
    {
        return $this->hasMany(TaskUpdate::class)->latest();
    }

    public function nudges()
    {
        return $this->hasMany(Nudge::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'owner');
    }
}
