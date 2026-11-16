
@extends('layouts.app')
@section('titulo', 'Estadísticas')
@section('contenido')
<div class="page-heading">
<div><p class="eyebrow">Lecturas e historial</p><h1>Estadísticas</h1><p>Consulta datos registrados y las lecturas actuales de los sensores.</p></div>
</div>
<form class="filtros" method="GET" action="{{ route('estadisticas.index') }}">
<div class="campo"><label for="sistema_riego_id">Sistema</label><select id="sistema_riego_id" name="sistema_riego_id"><option value="">Selecciona un sistema</option>@foreach ($sistemas as $opcion)<option value="{{ $opcion->id }}" @selected($sistema?->id === $opcion->id)>{{ $opcion->nombre }}</option>@endforeach</select></div>
<div class="campo"><label for="dias">Periodo</label><select id="dias" name="dias"><option value="7" @selected($diasSeleccionados === 7)>Últimos 7 días</option><option value="30" @selected($diasSeleccionados === 30)>Últimos 30 días</option><option value="90" @selected($diasSeleccionados === 90)>Últimos 90 días</option></select></div>
<button class="boton" type="submit">Filtrar</button>
</form>
<section class="graficos">
<article class="panel">
<div class="grafico-cabecera">
<div><h2>Consumo diario · histórico registrado</h2><p>{{ $sistema ? 'Litros asociados a '.$sistema->nombre : 'Registros históricos sin filtro por sistema' }}</p></div>
<span class="grafico-total">{{ number_format($totalLitros, 2) }} L</span>
</div>
<div class="grafico-contenedor">
<canvas id="grafico-dias" data-etiquetas="{{ json_encode($dias) }}" data-valores="{{ json_encode($litrosPorDia) }}" role="img" aria-label="Gráfico de litros utilizados por día"></canvas>
</div>
</article>

<article class="panel">
<div class="grafico-cabecera">
<div><h2>Humedad del suelo</h2><p>Última lectura del sensor</p></div>
<span class="grafico-total" id="humedad-suelo">{{ $ultimaMedicion ? number_format((float) $ultimaMedicion->humedad_suelo, 1) : '--' }} %</span>
</div>
</article>

<article class="panel">
<div class="grafico-cabecera">
<div><h2>Temperatura ambiente</h2><p>Última lectura del sensor</p></div>
<span class="grafico-total" id="temperatura">{{ $ultimaMedicion ? number_format((float) $ultimaMedicion->temperatura, 1) : '--' }} °C</span>
</div>
</article>

<article class="panel">
<div class="grafico-cabecera">
<div><h2>Humedad ambiental</h2><p>Última lectura del sensor</p></div>
<span class="grafico-total" id="humedad-ambiente">{{ $ultimaMedicion ? number_format((float) $ultimaMedicion->humedad_ambiente, 1) : '--' }} %</span>
</div>
</article>
</section>
<section class="panel">
<div class="panel-heading"><h2>Historial de riego y cambios</h2><p class="panel-copy">Las solicitudes sin conexión no representan riego ejecutado. Los registros históricos se asocian a un sistema solo cuando se eligió expresamente al crearlos.</p></div>
<div class="table-section-heading"><h3>Riegos registrados</h3></div>
<div class="tabla-scroll"><table><thead><tr><th>Fecha</th><th>Duración</th><th>Agua utilizada</th><th>Observaciones</th></tr></thead><tbody>
@forelse ($riegosAsociados as $riego)
<tr><td>{{ $riego->fecha->format('d/m/Y') }}</td><td>{{ $riego->duracion_min }} min</td><td>{{ number_format($riego->cantidad_litros, 2) }} L</td><td>{{ $riego->observaciones ?: '—' }}</td></tr>
@empty
<tr><td class="vacio" colspan="4"><strong>Sin riegos asociados a este sistema</strong>Los registros anteriores no se asignan automáticamente para evitar atribuciones incorrectas.</td></tr>
@endforelse
</tbody></table></div>
<div class="table-section-heading"><h3>Órdenes y cambios</h3></div>
<div class="tabla-scroll"><table><thead><tr><th>Fecha</th><th>Acción</th><th>Usuario</th><th>Resultado</th><th>Detalle</th></tr></thead><tbody>
@forelse ($comandos as $comando)
<tr><td>{{ $comando->solicitado_en->format('d/m/Y H:i') }}</td><td>{{ str_replace('_', ' ', ucfirst($comando->accion)) }}</td><td>{{ $comando->usuario?->name ?? 'Usuario no disponible' }}</td><td>{{ str_replace('_', ' ', $comando->resultado) }}</td><td>{{ $comando->detalle }}</td></tr>
@empty
<tr><td class="vacio" colspan="5"><strong>Sin órdenes ni cambios en el periodo</strong>No se registraron acciones asociadas a este sistema.</td></tr>
@endforelse
</tbody></table></div>
</section>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/graficos.js') }}"></script>

<script>
async function actualizarMediciones() {
    try {
        const respuesta = await fetch('/api/mediciones/ultima');

        if (!respuesta.ok) {
            throw new Error('No se pudieron obtener las mediciones');
        }

        const datos = await respuesta.json();

        document.getElementById('humedad-suelo').textContent =
            Number(datos.humedad_suelo).toFixed(1) + ' %';

        document.getElementById('temperatura').textContent =
            Number(datos.temperatura).toFixed(1) + ' °C';

        document.getElementById('humedad-ambiente').textContent =
            Number(datos.humedad_ambiente).toFixed(1) + ' %';

    } catch (error) {
        console.error('Error al actualizar las mediciones:', error);
    }
}

actualizarMediciones();
setInterval(actualizarMediciones, 34000);
</script>
@endpush
