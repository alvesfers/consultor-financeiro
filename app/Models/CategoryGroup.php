<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryGroup extends Model
{
    use HasFactory;

    protected $table = 'category_groups';

    protected $fillable = [
        'client_id',
        'name',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // Um grupo pertence a um cliente
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Um grupo possui várias categorias
    public function categories()
    {
        return $this->hasMany(Category::class, 'group_id');
    }
}
