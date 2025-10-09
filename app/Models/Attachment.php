<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = ['owner_type', 'owner_id', 'path', 'mime', 'size'];

    protected $casts = [
        'size' => 'integer',
    ];

    // Polymorphic: owner() pode ser Task, TaskUpdate, etc.
    public function owner()
    {
        return $this->morphTo();
    }
}
