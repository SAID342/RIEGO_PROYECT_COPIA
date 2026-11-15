@extends('layouts.app')
@section('titulo', 'Sistemas de riego')
@section('contenido')
<div class="page-heading">
<div><p class="eyebrow">Administración por cliente</p><h1>Sistemas de riego</h1><p>Cada sistema conserva su propio modo, configuración, lecturas e historial.</p></div>
</div>
<section class="panel system-create-panel">
<div class="panel-heading"><h2>Agregar sistema</h2><p class="panel-copy">El sistema se asociará a tu parcela principal. No requiere conectar hardware para registrarlo.</p></div>
<form class="system-create-form" method="POST" action="{{ route('sistemas.store') }}">
@csrf
<div class="campo"><label for="nombre">Nombre del sistema</label><input id="nombre" name="nombre" maxlength="100" value="{{ old('nombre') }}" required aria-invalid="{{ $errors->has('nombre') ? 'true' : 'false' }}"><small class="error" role="alert">{{ $errors->first('nombre') }}</small></div>
<div class="campo"><label for="descripcion">Descripción <span>(opcional)</span></label><input id="descripcion" name="descripcion" maxlength="1000" value="{{ old('descripcion') }}"><small class="error" role="alert">{{ $errors->first('descripcion') }}</small></div>
<button class="boton" type="submit">Crear sistema</button>
</form>
</section>
<section class="system-card-grid" aria-label="Sistemas registrados">
@forelse ($sistemas as $sistema)
<article class="panel system-list-card">
<div class="system-list-heading"><div><p class="eyebrow">{{ $sistema->parcela->usuario->name }}</p><h2>{{ $sistema->nombre }}</h2></div><span class="status-pill status-offline">Sin conexión</span></div>
<p>{{ $sistema->descripcion ?: 'Sin descripción.' }}</p>
<dl class="system-facts">
<div><dt>Modo</dt><dd>{{ strtoupper($sistema->configuracion->modo ?? 'manual') }}</dd></div>
<div><dt>Riego</dt><dd>{{ $sistema->riego_activo ? 'ACTIVO' : 'CERRADO' }}</dd></div>
<div><dt>Última lectura de prueba</dt><dd>{{ $sistema->ultimaMedicion?->fecha_lectura?->format('d/m/Y H:i') ?? 'Sin lecturas' }}</dd></div>
</dl>
<a class="boton boton-secundario" href="{{ route('sistemas.show', $sistema) }}">Abrir panel del sistema</a>
</article>
@empty
<div class="panel empty-note"><strong>Aún no hay sistemas registrados.</strong><p>Crea uno para administrar su configuración e ingresar lecturas de prueba.</p></div>
@endforelse
</section>
@endsection
