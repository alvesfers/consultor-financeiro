<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    public const STATUS_ATIVO = 'ativo';

    public const STATUS_PAUSADO = 'pausado';

    public const STATUS_ENCERRADO = 'encerrado';

    protected $fillable = ['user_id', 'consultant_id', 'status'];

    protected $casts = [
        'status' => 'string',
    ];

    // ---- Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    public function goals()
    {
        return $this->hasMany(Goal::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
