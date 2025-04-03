<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServidorTemporario extends Model
{
    use HasFactory;

    protected $table = 'servidor_temporarios';
    protected $primaryKey = 'pes_id';
    protected $fillable = [
        'pes_id',
        'st_data_admissao',
        'st_data_demissao'
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
