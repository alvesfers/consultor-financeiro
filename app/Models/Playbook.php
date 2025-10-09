<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playbook extends Model
{
    use HasFactory;

    protected $fillable = ['consultant_id', 'title', 'description'];

    // ---- Relations
    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function tasks()
    {
        return $this->hasMany(PlaybookTask::class);
    }
}
