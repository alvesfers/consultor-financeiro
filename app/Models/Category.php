<?php

// app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['group_id', 'client_id', 'name', 'is_active'];

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class, 'category_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeScoped($q, $cid)
    {
        return $q->whereNull('client_id')->orWhere('client_id', $cid);
    }

    public function group()
    {
        return $this->belongsTo(CategoryGroup::class, 'group_id');
    }
}
