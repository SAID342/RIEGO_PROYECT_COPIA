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

        $dias = collect(range($cantidadDias - 1, 0))
            ->map(fn ($atras) => now()->subDays($atras)->toDateString());

        $riegos = Riego::visiblesPara($usuario)
            ->when($sistema, fn ($consulta) => $consulta->where('sistema_riego_id', $sistema->id))
            ->where('fecha', '>=', $dias->first())
            ->get();

        $litrosPorDia = $dias->map(
            fn ($dia) => round(
                $riegos->filter(fn ($r) => $r->fecha->toDateString() === $dia)->sum('cantidad_litros'),
                2
            )
        );

        $unidadNivel = $sistema?->configuracion?->unidad_nivel_agua
            ?? 'unidad registrada';

        // Obtener mediciones reales enviadas por el ESP32
        $mediciones = Medicion::query()
            ->where('created_at', '>=', $dias->first())
            ->orderBy('created_at')
            ->get();

        // Preparar los datos para los cuatro gráficos
        $lecturasPorTipo = collect([
            'nivel_agua' => 'nivel_agua',
            'humedad_suelo' => 'humedad_suelo',
            'temperatura' => 'temperatura',
            'humedad_ambiental' => 'humedad_ambiente',
        ])->mapWithKeys(function ($columna, $tipo) use ($dias, $mediciones) {

            $porDia = $mediciones->groupBy(
                fn ($medicion) => $medicion->created_at->toDateString()
            );

            return [
                $tipo => $dias->map(function ($dia) use ($porDia, $columna) {

                    $valores = $porDia->get($dia, collect())->pluck($columna);

                    return $valores->isEmpty()
                        ? null
                        : round((float) $valores->avg(), 2);

                })->values()
            ];
        });

        // Obtener la última medición para mostrar los valores actuales
        $ultimaMedicion = Medicion::latest('id')->first();

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
            'ultimaMedicion' => $ultimaMedicion,
            'comandos' => $comandos,
            'riegosAsociados' => $riegosAsociados,
            'unidadNivel' => $unidadNivel,
        ]);
    }
}