<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServidorEfetivo extends Model
{
    use HasFactory;

    protected $table = 'servidor_efetivos';
    protected $primaryKey = 'pes_id';
    protected $fillable = [
        'pes_id',
        'se_matricula'
    ];
    public $incrementing = false;

    public function pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'pes_id');
    }

    public function lotacoes()
    {
        return $this->hasMany(Lotacao::class, 'pes_id');
    }
}
