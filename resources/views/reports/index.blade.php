@extends('layouts.app')

@section('body')
@php
    $fmt = fn ($seconds) => intdiv((int) $seconds, 3600).'h '.str_pad((string) intdiv(((int) $seconds % 3600), 60), 2, '0', STR_PAD_LEFT).'m';
    $tabs = [
        'overview' => ['Resumen', 'fa-solid fa-chart-pie'],
        'attendance' => ['Asistencia', 'fa-solid fa-user-clock'],
        'productivity' => ['Productividad', 'fa-solid fa-arrow-trend-up'],
        'incidents' => ['Incidencias', 'fa-solid fa-triangle-exclamation'],
    ];
    $query = request()->except('tab', 'page');
    $today = now($corporateTimezone)->toDateString();
    $quickRanges = [
        ['Hoy', $today, $today],
        ['7 días', now($corporateTimezone)->subDays(6)->toDateString(), $today],
        ['30 días', now($corporateTimezone)->subDays(29)->toDateString(), $today],
    ];
@endphp
<div class="flex min-h-screen overflow-hidden bg-slate-100/45 font-sans text-slate-800">
    @include('partials.sidebar')
    <main class="flex h-screen min-w-0 flex-1 flex-col overflow-hidden bg-slate-50/20">
        @include('partials.header', ['title' => 'Centro de Reportes'])
        <div class="flex-1 overflow-y-auto bg-slate-50/60 p-6 lg:p-8">
            <section class="app-section-hero reports-hero mb-6">
                <div class="app-section-hero-copy">
                    <p><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Inteligencia operativa</p>
                    <h2>{{ $workliveBrand?->menu_reports ?: 'Reportes y Exportación' }}</h2>
                    <span>Consolidado de {{ $from }} a {{ $to }} · Zona {{ str_replace('_', ' ', $corporateTimezone) }}.</span>
                </div>
                <div class="app-section-hero-metrics">
                    <div><i class="fa-solid fa-users" aria-hidden="true"></i><small>Colaboradores</small><b>{{ $metrics->employees }}</b></div>
                    <div class="reports-kpi-active"><i class="fa-solid fa-stopwatch" aria-hidden="true"></i><small>Tiempo activo</small><b>{{ $fmt($metrics->active) }}</b></div>
                    <div class="reports-kpi-idle"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i><small>Tiempo inactivo</small><b>{{ $fmt($metrics->idle) }}</b></div>
                    <div><i class="fa-solid fa-bolt" aria-hidden="true"></i><small>Productividad</small><b>{{ $metrics->productivity }}%</b></div>
                    <div><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><small>Incidencias</small><b>{{ $metrics->incidents }}</b></div>
                </div>
                <div class="app-section-hero-actions">
                    <a href="{{ route('reports.export.pdf', request()->query()) }}"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i><span>PDF</span></a>
                    <a href="{{ route('reports.export.xlsx', request()->query()) }}"><i class="fa-solid fa-file-excel" aria-hidden="true"></i><span>Excel</span></a>
                    <a href="{{ route('reports.export', request()->query()) }}"><i class="fa-solid fa-file-csv" aria-hidden="true"></i><span>CSV</span></a>
                </div>
            </section>

            <form method="get" class="reports-filter-panel mb-6 rounded-2xl border border-slate-100 bg-white shadow-sm">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="reports-filter-head">
                    <div>
                        <p class="flex items-center gap-2 text-xs font-bold text-slate-800"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"><i class="fa-solid fa-sliders" aria-hidden="true"></i></span> Filtros del reporte</p>
                        <span class="mt-1 block text-[10px] text-slate-400">Se aplican a las cuatro vistas y a todas las exportaciones.</span>
                    </div>
                    <div class="reports-quick-ranges"><span>Periodo rápido</span>@foreach($quickRanges as [$label, $rangeFrom, $rangeTo])<a href="{{ route('reports', array_merge(request()->except('date_from', 'date_to', 'page'), ['date_from' => $rangeFrom, 'date_to' => $rangeTo])) }}">{{ $label }}</a>@endforeach</div>
                </div>
                <div class="reports-filter-grid">
                    <label><span>Desde</span><input type="date" name="date_from" value="{{ $from }}"></label>
                    <label><span>Hasta</span><input type="date" name="date_to" value="{{ $to }}"></label>
                    <label><span>Colaborador</span><select name="employee_id"><option value="All">Todos los colaboradores</option>@foreach($employees as $employee)<option value="{{ $employee->employee_id }}" @selected(request('employee_id', 'All') === $employee->employee_id)>{{ $employee->employee_name }}</option>@endforeach</select></label>
                    <label><span>Departamento</span><select name="department"><option value="All">Todos los departamentos</option>@foreach($departments as $department)<option value="{{ $department }}" @selected(request('department', 'All') === $department)>{{ $department }}</option>@endforeach</select></label>
                    <label><span>País</span><select name="country"><option value="All">Todos los países</option>@foreach($countries as $country)<option value="{{ $country }}" @selected(request('country', 'All') === $country)>{{ $country }}</option>@endforeach</select></label>
                </div>
                <div class="reports-filter-actions">
                    <span class="font-mono text-[10px] text-slate-400">{{ $from }} → {{ $to }}</span>
                    <div class="flex items-center gap-2"><a href="{{ route('reports', ['tab' => $tab]) }}"><i class="fa-solid fa-filter-circle-xmark" aria-hidden="true"></i> Limpiar</a><button><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i> Actualizar reporte</button></div>
                </div>
            </form>

            <nav class="mb-5 flex gap-2 overflow-x-auto border-b border-slate-200 pb-0">@foreach($tabs as $key => [$label, $icon])<a href="{{ route('reports', array_merge($query, ['tab' => $key])) }}" class="inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-4 py-3 text-xs font-bold transition {{ $tab === $key ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-400 hover:text-slate-700' }}"><i class="{{ $icon }}" aria-hidden="true"></i>{{ $label }}</a>@endforeach</nav>

            <section class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                @if($tab === 'incidents')
                    <div class="border-b border-slate-100 p-5"><h3 class="font-bold text-slate-900">Incidencias y bloqueos</h3><p class="mt-1 text-xs text-slate-500">Eventos de bloqueo, sitios restringidos y sesiones bloqueadas dentro del periodo.</p></div>
                    <div class="overflow-x-auto"><table class="min-w-[780px] w-full text-left text-xs"><thead class="bg-slate-50 font-mono text-[9px] uppercase tracking-wider text-slate-400"><tr><th class="px-5 py-3">Momento</th><th class="px-5 py-3">Empleado</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Aplicación / dominio</th><th class="px-5 py-3">Detalle</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($incidents as $event)<tr><td class="px-5 py-3 font-mono text-slate-500">{{ $event->display_timestamp->format('d/m/Y H:i') }}</td><td class="px-5 py-3 font-bold text-slate-800">{{ $event->employee_name }}</td><td class="px-5 py-3"><span class="rounded-full bg-rose-50 px-2 py-1 font-mono text-[9px] font-bold text-rose-700">{{ $event->event_type }}</span></td><td class="px-5 py-3 font-semibold text-slate-700">{{ $event->app ?: $event->domain ?: '—' }}</td><td class="px-5 py-3 text-slate-500">{{ $event->title ?: 'Sin detalle adicional' }}</td></tr>@empty<tr><td colspan="5" class="px-5 py-16 text-center text-xs text-slate-400">No hay incidencias para los filtros seleccionados.</td></tr>@endforelse</tbody></table></div>
                @elseif($tab === 'attendance')
                    <div class="reports-view-heading"><div><h3><i class="fa-solid fa-user-clock" aria-hidden="true"></i> Asistencia y cumplimiento</h3><p>Primer y último registro del día según la jornada configurada: {{ $workStart }} – {{ $workEnd }} · tolerancia de {{ $lateGrace }} min.</p></div><div class="reports-view-summary"><span>Puntuales <b>{{ $attendanceMetrics->punctual }}</b></span><span>Retardos <b>{{ $attendanceMetrics->late }}</b></span><span>Salidas tempranas <b>{{ $attendanceMetrics->early }}</b></span></div></div>
                    <div class="overflow-x-auto"><table class="min-w-[940px] w-full text-left text-xs"><thead class="bg-slate-50 font-mono text-[9px] uppercase tracking-wider text-slate-400"><tr><th class="px-5 py-3">Colaborador</th><th class="px-5 py-3">Departamento</th><th class="px-5 py-3 text-center">Días</th><th class="px-5 py-3 text-center">Primer registro</th><th class="px-5 py-3 text-center">Último registro</th><th class="px-5 py-3 text-center">Retardos</th><th class="px-5 py-3 text-center">Salidas tempranas</th><th class="px-5 py-3 text-right">Cumplimiento</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($attendanceRows->sortByDesc('compliance') as $row)<tr class="transition hover:bg-slate-50"><td class="px-5 py-4"><a href="{{ route('employees.show', $row->employee_id) }}" class="font-bold text-slate-900 hover:text-indigo-600">{{ $row->employee_name }}</a></td><td class="px-5 py-4 text-slate-600">{{ $row->department }}</td><td class="px-5 py-4 text-center font-mono text-slate-600">{{ $row->days }}</td><td class="px-5 py-4 text-center font-mono font-semibold text-slate-700">{{ $row->first_check_in?->format('H:i') ?? '—' }}</td><td class="px-5 py-4 text-center font-mono font-semibold text-slate-700">{{ $row->last_check_out?->format('H:i') ?? '—' }}</td><td class="px-5 py-4 text-center"><span class="rounded-full px-2 py-1 font-mono text-[10px] font-bold {{ $row->late_days ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $row->late_days }}</span></td><td class="px-5 py-4 text-center"><span class="rounded-full px-2 py-1 font-mono text-[10px] font-bold {{ $row->early_days ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $row->early_days }}</span></td><td class="px-5 py-4 text-right"><span class="inline-flex min-w-12 justify-center rounded-full px-2 py-1 font-mono text-[10px] font-bold {{ $row->compliance >= 85 ? 'bg-emerald-50 text-emerald-700' : ($row->compliance >= 60 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') }}">{{ $row->compliance }}%</span></td></tr>@empty<tr><td colspan="8" class="px-5 py-16 text-center text-xs text-slate-400">No hay registros de asistencia para los filtros seleccionados.</td></tr>@endforelse</tbody></table></div>
                @elseif($tab === 'productivity')
                    <div class="reports-view-heading"><div><h3><i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i> Ranking de productividad</h3><p>El índice de foco compara el tiempo activo contra el tiempo activo e inactivo del periodo.</p></div><div class="reports-view-summary"><span>Activo <b>{{ $fmt($metrics->active) }}</b></span><span>Índice global <b>{{ $metrics->productivity }}%</b></span></div></div>
                    <div class="overflow-x-auto"><table class="min-w-[980px] w-full text-left text-xs"><thead class="bg-slate-50 font-mono text-[9px] uppercase tracking-wider text-slate-400"><tr><th class="px-5 py-3">#</th><th class="px-5 py-3">Colaborador</th><th class="px-5 py-3">Área</th><th class="px-5 py-3 text-right">Tiempo activo</th><th class="px-5 py-3 text-right">Tiempo inactivo</th><th class="px-5 py-3">Aporte al equipo</th><th class="px-5 py-3 text-right">Índice de foco</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($employeeRows->sortByDesc('productivity')->values() as $row)@php($contribution = $metrics->active > 0 ? round(($row->active / $metrics->active) * 100) : 0)<tr class="transition hover:bg-slate-50"><td class="px-5 py-4 font-mono font-bold text-indigo-500">{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</td><td class="px-5 py-4"><a href="{{ route('employees.show', $row->employee_id) }}" class="font-bold text-slate-900 hover:text-indigo-600">{{ $row->employee_name }}</a><span class="mt-0.5 block text-[10px] text-slate-400">{{ $row->country }}</span></td><td class="px-5 py-4 font-semibold text-slate-600">{{ $row->department }}</td><td class="px-5 py-4 text-right font-mono font-bold text-emerald-600">{{ $fmt($row->active) }}</td><td class="px-5 py-4 text-right font-mono text-amber-500">{{ $fmt($row->idle) }}</td><td class="px-5 py-4"><div class="reports-contribution"><span><i style="width: {{ min(100, $contribution) }}%"></i></span><b>{{ $contribution }}%</b></div></td><td class="px-5 py-4 text-right"><span class="inline-flex min-w-12 justify-center rounded-full px-2 py-1 font-mono text-[10px] font-bold {{ $row->productivity >= 75 ? 'bg-emerald-50 text-emerald-700' : ($row->productivity >= 50 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') }}">{{ $row->productivity }}%</span></td></tr>@empty<tr><td colspan="7" class="px-5 py-16 text-center text-xs text-slate-400">No existen datos de productividad para los filtros seleccionados.</td></tr>@endforelse</tbody></table></div>
                @else
                    <div class="reports-view-heading"><div><h3><i class="fa-solid fa-chart-pie" aria-hidden="true"></i> Resumen por colaborador</h3><p>{{ $employeeRows->count() }} colaboradores con actividad consolidada en el periodo.</p></div><span class="rounded-lg bg-slate-50 px-3 py-1.5 font-mono text-[10px] font-bold text-slate-500">{{ $from }} → {{ $to }}</span></div>
                    <div class="overflow-x-auto"><table class="min-w-[900px] w-full text-left text-xs"><thead class="bg-slate-50 font-mono text-[9px] uppercase tracking-wider text-slate-400"><tr><th class="px-5 py-3">Colaborador</th><th class="px-5 py-3">Departamento / País</th><th class="px-5 py-3 text-center">Días</th><th class="px-5 py-3 text-right">Activo</th><th class="px-5 py-3 text-right">Inactivo</th><th class="px-5 py-3 text-right">Bloqueado</th><th class="px-5 py-3 text-right">Productividad</th><th class="px-5 py-3 text-right">Jornada registrada</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($employeeRows->sortByDesc('active') as $row)<tr class="transition hover:bg-slate-50"><td class="px-5 py-4"><a href="{{ route('employees.show', $row->employee_id) }}" class="font-bold text-slate-900 hover:text-indigo-600">{{ $row->employee_name }}</a></td><td class="px-5 py-4"><span class="block font-semibold text-slate-600">{{ $row->department }}</span><span class="text-[10px] text-slate-400">{{ $row->country }}</span></td><td class="px-5 py-4 text-center font-mono text-slate-600">{{ $row->days }}</td><td class="px-5 py-4 text-right font-mono font-bold text-emerald-600">{{ $fmt($row->active) }}</td><td class="px-5 py-4 text-right font-mono text-amber-500">{{ $fmt($row->idle) }}</td><td class="px-5 py-4 text-right font-mono text-indigo-500">{{ $fmt($row->locked) }}</td><td class="px-5 py-4 text-right"><span class="inline-flex min-w-12 justify-center rounded-full px-2 py-1 font-mono text-[10px] font-bold {{ $row->productivity >= 75 ? 'bg-emerald-50 text-emerald-700' : ($row->productivity >= 50 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') }}">{{ $row->productivity }}%</span></td><td class="px-5 py-4 text-right font-mono text-[10px] text-slate-500">{{ $row->first_activity ? \Carbon\Carbon::parse($row->first_activity, 'UTC')->setTimezone($corporateTimezone)->format('H:i') : '—' }} – {{ $row->last_activity ? \Carbon\Carbon::parse($row->last_activity, 'UTC')->setTimezone($corporateTimezone)->format('H:i') : '—' }}</td></tr>@empty<tr><td colspan="8" class="px-5 py-16 text-center text-xs text-slate-400">No existen resúmenes para el periodo seleccionado.</td></tr>@endforelse</tbody></table></div>
                @endif
            </section>
        </div>
    </main>
</div>
@endsection
