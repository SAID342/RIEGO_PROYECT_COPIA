<?php

namespace App\Http\Controllers;

use App\Models\Medicion;
use App\Models\Riego;
use App\Models\SistemaRiego;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EstadisticaController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $filtros = $request->validate([
            'sistema_riego_id' => ['nullable', 'integer', 'exists:sistemas_riego,id'],
            'dias' => ['nullable', 'integer', 'in:7,30,90'],
        ]);
        $sistemas = SistemaRiego::visiblesPara($usuario)->orderBy('nombre')->get();
        $sistema = isset($filtros['sistema_riego_id'])
            ? $sistemas->firstWhere('id', (int) $filtros['sistema_riego_id'])
            : $sistemas->first();

        abort_if(isset($filtros['sistema_riego_id']) && ! $sistema, 404);

        $cantidadDias = (int) ($filtros['dias'] ?? 30);
        $dias = collect(range($cantidadDias - 1, 0))->map(fn ($atras) => now()->subDays($atras)->toDateString());

        $riegos = Riego::visiblesPara($usuario)
            ->when($sistema, fn ($consulta) => $consulta->where('sistema_riego_id', $sistema->id))
            ->where('fecha', '>=', $dias->first())
            ->get();

        $litrosPorDia = $dias->map(
            fn ($dia) => round($riegos->filter(fn ($r) => $r->fecha->toDateString() === $dia)->sum('cantidad_litros'), 2)
        );

        $unidadNivel = $sistema?->configuracion?->unidad_nivel_agua
            ?? $sistema?->mediciones()->where('tipo', 'nivel_agua')->latest('fecha_lectura')->value('unidad')
            ?? 'unidad registrada';
        $lecturasPorTipo = collect(['nivel_agua', 'humedad_suelo', 'temperatura', 'humedad_ambiental'])
            ->mapWithKeys(function (string $tipo) use ($dias, $sistema, $unidadNivel) {
                $lecturas = $sistema
                    ? Medicion::query()
                        ->where('sistema_riego_id', $sistema->id)
                        ->where('tipo', $tipo)
                        ->when($tipo === 'nivel_agua', fn ($query) => $query->where('unidad', $unidadNivel))
                        ->where('fecha_lectura', '>=', $dias->first())
                        ->orderBy('fecha_lectura')
                        ->get()
                    : collect();

                $porDia = $lecturas->groupBy(fn (Medicion $medicion) => $medicion->fecha_lectura->toDateString());

                return [$tipo => $dias->map(function (string $dia) use ($porDia) {
                    $valores = $porDia->get($dia, collect())->pluck('valor');

                    return $valores->isEmpty() ? null : round((float) $valores->avg(), 2);
                })->values()];
            });

        $comandos = $sistema
            ? $sistema->comandos()
                ->with('usuario')
                ->where('solicitado_en', '>=', $dias->first())
                ->latest('solicitado_en')
                ->limit(100)
                ->get()
            : collect();
        $riegosAsociados = $sistema
            ? $sistema->riegos()
                ->where('fecha', '>=', $dias->first())
                ->latest('fecha')
                ->limit(100)
                ->get()
            : collect();

        return view('estadisticas.index', [
            'dias' => $dias->values(),
            'litrosPorDia' => $litrosPorDia->values(),
            'totalLitros' => $riegos->sum('cantidad_litros'),
            'sistemas' => $sistemas,
            'sistema' => $sistema,
            'diasSeleccionados' => $cantidadDias,
            'lecturasPorTipo' => $lecturasPorTipo,
            'comandos' => $comandos,
            'riegosAsociados' => $riegosAsociados,
            'unidadNivel' => $unidadNivel,
        ]);
    }
}
