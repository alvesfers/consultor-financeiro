<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ---- Relations
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    // ---- Scopes
    public function scopeRoots($q)
    {
        return $q->whereNull('parent_id');
    }

    public function scopeActives($q)
    {
        return $q->where('is_active', true);
    }
}
