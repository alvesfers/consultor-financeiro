<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaybookTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'playbook_id', 'title', 'description', 'type', 'frequency', 'custom_rrule',
        'offset_days_from_start', 'default_due_hour',
    ];

    protected $casts = [
        'offset_days_from_start' => 'integer',
        'default_due_hour' => 'integer',
    ];

    // ---- Relations
    public function playbook()
    {
        return $this->belongsTo(Playbook::class);
    }
}
