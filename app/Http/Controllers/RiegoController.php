<?php

namespace App\Http\Controllers;

use App\Http\Requests\RiegoRequest;
use App\Models\Riego;
use App\Models\SistemaRiego;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiegoController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'fecha' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
            'sistema_riego_id' => ['nullable', 'integer', 'exists:sistemas_riego,id'],
        ]);
        if (! empty($filtros['sistema_riego_id'])) {
            abort_unless(
                SistemaRiego::visiblesPara($request->user())->whereKey($filtros['sistema_riego_id'])->exists(),
                404
            );
        }

        $riegos = Riego::visiblesPara($request->user())
            ->with(['parcela', 'sistemaRiego'])
            ->filtrar($filtros)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('riegos.index', [
            'riegos' => $riegos,
            'filtros' => $filtros,
            'sistemas' => SistemaRiego::visiblesPara($request->user())->orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return view('riegos.form', [
            'riego' => new Riego(['fecha' => now()->toDateString()]),
            'sistemas' => SistemaRiego::visiblesPara(request()->user())->orderBy('nombre')->get(),
        ]);
    }

    public function store(RiegoRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $sistema = $this->sistemaSeleccionado($request, $datos['sistema_riego_id'] ?? null);
        $parcela = $sistema?->parcela ?? $request->user()->parcelaPrincipal();
        $riego = $parcela->riegos()->create($datos);
        $riego->generarAlerta();

        return redirect()->route('riegos.index')->with('ok', 'Riego guardado correctamente.');
    }

    public function edit(Request $request, Riego $riego): View
    {
        abort_unless($riego->perteneceA($request->user()), 403);

        return view('riegos.form', [
            'riego' => $riego,
            'sistemas' => SistemaRiego::visiblesPara($request->user())->orderBy('nombre')->get(),
        ]);
    }

    public function update(RiegoRequest $request, Riego $riego): RedirectResponse
    {
        abort_unless($riego->perteneceA($request->user()), 403);

        $datos = $request->validated();
        $this->sistemaSeleccionado($request, $datos['sistema_riego_id'] ?? null);
        if (! empty($datos['sistema_riego_id'])) {
            $datos['parcela_id'] = SistemaRiego::findOrFail($datos['sistema_riego_id'])->parcela_id;
        }
        $riego->update($datos);
        $riego->generarAlerta();

        return redirect()->route('riegos.index')->with('ok', 'Riego actualizado correctamente.');
    }

    public function destroy(Request $request, Riego $riego): RedirectResponse
    {
        abort_unless($riego->perteneceA($request->user()), 403);

        $riego->delete();

        return redirect()->route('riegos.index')->with('ok', 'Riego eliminado correctamente.');
    }

    private function sistemaSeleccionado(Request $request, ?int $sistemaId): ?SistemaRiego
    {
        if ($sistemaId === null) {
            return null;
        }

        return SistemaRiego::visiblesPara($request->user())->whereKey($sistemaId)->firstOrFail();
    }
}
