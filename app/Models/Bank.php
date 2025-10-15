<?php

// app/Models/Bank.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $fillable = [
        'name','slug','code','logo_svg',
        'color_primary','color_secondary','color_bg','color_text',
    ];

    public function accounts() {
        return $this->hasMany(Account::class);
    }
}
