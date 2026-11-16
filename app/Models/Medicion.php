<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Medicion extends Model
{
    protected $table = 'mediciones';

    protected $fillable = [
        'sistema_riego_id',
        'sensor_id',
        'tipo',
        'valor',
        'unidad',
        'fecha_lectura',
        'es_prueba',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'float',
            'fecha_lectura' => 'datetime',
            'es_prueba' => 'boolean',
        ];
    }

    public function sistemaRiego(): BelongsTo
    {
        return $this->belongsTo(SistemaRiego::class, 'sistema_riego_id');
    }

    public function scopeVisiblesPara(Builder $consulta, User $usuario): Builder
    {
        return $consulta->whereHas('sistemaRiego', fn (Builder $q) => $q->visiblesPara($usuario));
    }
}
