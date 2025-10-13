<?php
// app/Models/Subcategory.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $fillable = ['category_id','client_id','name','is_active'];

    public function category(){ return $this->belongsTo(Category::class); }

    public function scopeActive($q){ return $q->where('is_active', true); }
    public function scopeScoped($q,$cid){ return $q->whereNull('client_id')->orWhere('client_id',$cid); }
}
