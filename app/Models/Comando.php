<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comando extends Model
{
    protected $table = 'comandos';

    protected $fillable = [
        'sistema_riego_id',
        'user_id',
        'accion',
        'resultado',
        'detalle',
        'solicitado_en',
        'procesado_en',
    ];

    protected function casts(): array
    {
        return [
            'solicitado_en' => 'datetime',
            'procesado_en' => 'datetime',
        ];
    }

    public function sistemaRiego(): BelongsTo
    {
        return $this->belongsTo(SistemaRiego::class, 'sistema_riego_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
