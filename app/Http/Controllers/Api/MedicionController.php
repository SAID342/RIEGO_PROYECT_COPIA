<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicionController extends Controller
{
    public function guardar(Request $request)
    {
        $datos = $request->validate([
            'humedad_suelo' => 'required|numeric|min:0|max:100',
            'nivel_agua' => 'required|numeric|min:0|max:100',
            'temperatura' => 'required|numeric',
            'humedad_ambiente' => 'required|numeric|min:0|max:100',
        ]);

        $id = DB::table('mediciones')->insertGetId([
            'humedad_suelo' => $datos['humedad_suelo'],
            'nivel_agua' => $datos['nivel_agua'],
            'temperatura' => $datos['temperatura'],
            'humedad_ambiente' => $datos['humedad_ambiente'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Medición guardada correctamente',
            'id' => $id,
            'datos' => $datos
        ], 201);
    }
    public function ultima()
{
    $medicion = \App\Models\Medicion::latest('id')->first();

    if (!$medicion) {
        return response()->json([
            'mensaje' => 'No hay mediciones registradas'
        ], 404);
    }

    return response()->json($medicion);
}
}
