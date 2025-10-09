<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nudge extends Model
{
    use HasFactory;

    public const CHANNEL_IN_APP = 'in_app';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_WHATS = 'whatsapp';

    public const CHANNEL_SMS = 'sms';

    public const SENT_BY_AUTO = 'auto';

    public const SENT_BY_CONSULTANT = 'consultant';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = ['task_id', 'channel', 'sent_by', 'sent_at', 'status'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    // ---- Relations
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
