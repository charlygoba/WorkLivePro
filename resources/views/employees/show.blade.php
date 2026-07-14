@extends('layouts.app')
@section('body')
@php
 $tab=request('tab','overview');
 $fmt=function($seconds) { $seconds=max(0,(int)$seconds); $hours=intdiv($seconds,3600); $minutes=intdiv($seconds%3600,60); $remaining=$seconds%60; if($hours) return $hours.'h '.str_pad((string)$minutes,2,'0',STR_PAD_LEFT).'m'; if($minutes) return $minutes.'m '.str_pad((string)$remaining,2,'0',STR_PAD_LEFT).'s'; return $remaining.'s'; };
 $statusClass=match($employee->status){'active'=>'bg-emerald-500 text-white animate-pulse','online'=>'bg-emerald-400 text-white','idle'=>'bg-amber-400 text-slate-900','locked'=>'bg-indigo-500 text-white',default=>'bg-slate-300 text-slate-700'};
 $statusLabel=match($employee->status){'active'=>'Activo (En Pantalla)','online'=>'Online (Disponible)','idle'=>'Inactivo (Ausente)','locked'=>'Bloqueado (Suspendido)',default=>'Offline'};
 $history=$summaries->take(7)->reverse()->values();$max=max(1,$history->flatMap(fn($s)=>[$s->total_active_seconds,$s->total_idle_seconds])->max());$count=max(1,$history->count()-1);
 $line=fn($field)=>$history->map(fn($s,$i)=>round(($i/$count)*900).','.round(218-($s->{$field}/$max*170)))->join(' ');
 $appMax=max(1,$appTotals->max() ?: 1); $domainMax=max(1,$domainTotals->max() ?: 1);
@endphp
<div class="flex bg-slate-100/45 text-slate-800 font-sans min-h-screen overflow-hidden">@include('partials.sidebar')<main class="flex-1 flex flex-col h-screen min-w-0 bg-slate-50/20 overflow-hidden">@include('partials.header',['title'=>'Expediente del Colaborador'])<div class="flex-1 overflow-y-auto p-8 bg-slate-50/60">
<div class="flex items-center space-x-3 mb-6"><a href="{{ route('employees') }}" class="p-2 border border-slate-100 hover:border-slate-300 rounded-lg hover:bg-white text-gray-500 bg-slate-50">←</a><div><span class="text-[10px] text-gray-400 font-mono font-bold uppercase tracking-wider block">EXPEDIENTE LABORAL</span><h2 class="text-xl font-extrabold text-slate-950 mt-0.5">{{ $employee->name }}</h2></div></div>
<div class="grid grid-cols-1 xl:grid-cols-3 gap-8 mb-8"><section class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between"><div class="flex items-center space-x-4"><div class="w-14 h-14 bg-indigo-100 rounded-2xl flex items-center justify-center font-black text-indigo-800 text-lg">{{ collect(explode(' ',$employee->name))->filter()->map(fn($n)=>mb_substr($n,0,1))->join('') }}</div><div><span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold font-mono uppercase tracking-wider {{ $statusClass }}">{{ $statusLabel }}</span><p class="text-xs text-gray-400 font-mono mt-1.5">{{ $employee->timezone }}</p></div></div><div class="my-6 border-t border-slate-100 pt-5 space-y-4"><div class="flex justify-between text-xs"><span class="text-gray-400 font-medium">Departamento:</span><span class="font-bold text-gray-800 bg-slate-100 py-0.5 px-2 rounded">{{ $employee->department }}</span></div><div class="flex justify-between text-xs"><span class="text-gray-400 font-medium">País de Residencia:</span><span class="font-semibold text-gray-800">⌖ {{ $employee->country }}</span></div><div class="flex justify-between text-xs"><span class="text-gray-400 font-medium">Último Dispositivo:</span><span class="font-semibold text-gray-800 font-mono text-[11px]">{{ $employee->current_app ? 'LPT-Remote (Active)' : 'Remoto' }}</span></div><div class="flex justify-between text-xs"><span class="text-gray-400 font-medium">Última Conexión:</span><span class="font-mono text-slate-500 font-semibold">{{ $employee->last_active ? date('H:i',strtotime($employee->last_active)) : '—' }}</span></div></div><div class="rounded-xl border border-slate-100 bg-slate-50 p-3"><div class="mb-2 flex items-center justify-between"><span class="font-mono text-[9px] font-bold uppercase tracking-wider text-slate-400">Tiempo registrado</span><span class="font-mono text-[9px] text-slate-400">Hoy {{ $timeMetrics['today'] }}</span></div><div class="grid grid-cols-2 gap-x-3 gap-y-3 text-xs"><div><span class="block font-mono text-[9px] font-bold uppercase text-slate-400">Activo hoy</span><span class="mt-1 block font-mono text-sm font-black text-emerald-600">{{ $fmt($timeMetrics['todayActive']) }}</span></div><div class="border-l border-slate-200 pl-3"><span class="block font-mono text-[9px] font-bold uppercase text-slate-400">Inactivo hoy</span><span class="mt-1 block font-mono text-sm font-black text-amber-500">{{ $fmt($timeMetrics['todayIdle']) }}</span></div><div class="border-t border-slate-200 pt-2"><span class="block font-mono text-[9px] font-bold uppercase text-slate-400">Activo semana</span><span class="mt-1 block font-mono text-sm font-black text-indigo-600">{{ $fmt($timeMetrics['weekActive']) }}</span></div><div class="border-l border-t border-slate-200 pl-3 pt-2"><span class="block font-mono text-[9px] font-bold uppercase text-slate-400">Inactivo semana</span><span class="mt-1 block font-mono text-sm font-black text-slate-600">{{ $fmt($timeMetrics['weekIdle']) }}</span></div></div><p class="mt-2 font-mono text-[9px] text-slate-400">Semana en curso desde {{ $timeMetrics['weekStart'] }} · zona {{ $employee->timezone }}</p></div></section>
<section class="bg-slate-900 text-slate-100 p-6 rounded-2xl shadow-sm xl:col-span-2 flex flex-col justify-between border border-slate-800"><div><div class="flex items-center justify-between mb-4"><div class="flex items-center space-x-2"><span class="relative flex h-2.5 w-2.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span></span><span class="text-xs font-mono font-bold text-slate-400 uppercase tracking-widest">FOCO DEL AGENTE EN CURSO</span></div><span class="text-[10px] font-mono text-slate-500">Polling Interval: Real-time</span></div><h3 class="text-2xl font-black text-white">{{ $employee->current_app ?: 'Sin actividad reportada' }}</h3><p class="text-xs text-slate-400 font-mono italic mt-1 pb-4 border-b border-slate-800">“{{ $employee->current_title ?: 'Sin título reportado' }}”</p></div><div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6"><div class="p-4 rounded-xl bg-slate-950/40 border border-slate-800/80"><span class="text-[10px] text-slate-500 font-mono font-bold uppercase tracking-wider block">DOMINIO EN PANTALLA</span><div class="mt-2 text-sm font-semibold font-mono text-slate-200">◎ {{ $employee->current_domain && $employee->current_domain !== 'none' ? $employee->current_domain : 'Ninguno / App Local' }}</div></div><div class="p-4 rounded-xl bg-slate-950/40 border border-slate-800/80"><span class="text-[10px] text-slate-500 font-mono font-bold uppercase tracking-wider block">SINCRO AGENTE CLIENTE</span><div class="mt-2 text-xs font-semibold text-slate-300 font-mono">▱ {{ $employee->current_app ? 'Windows 11 Client x64' : '—' }}</div></div></div></section></div>
<nav class="flex border-b border-slate-200 mb-6 gap-6 text-xs font-semibold overflow-x-auto">@foreach(['overview'=>'Métricas Históricas y Distribución','events'=>'Timeline Diario de Eventos ('.$eventTotal.')','apps'=>'Top Apps e Incidentes de Foco','device'=>'Equipo y Hardware ('.$devices->count().')'] as $key=>$label)<a href="{{ route('employees.show',$employee->id) }}?tab={{ $key }}" class="whitespace-nowrap pb-3 border-b-2 tracking-wide font-bold {{ $tab===$key?'border-indigo-600 text-slate-900':'border-transparent text-slate-400 hover:text-slate-700' }}">{{ $label }}</a>@endforeach</nav>
@if($tab==='overview')<div class="space-y-8"><section class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm"><div class="flex items-center justify-between mb-6"><div><h3 class="text-sm font-bold text-gray-900">Historial Diario de Foco</h3><p class="text-xs text-gray-500 mt-0.5">Distribución cronológica de tiempo productivo vs inactivo (últimos 7 días)</p></div><span class="px-2 py-0.5 bg-slate-100 font-mono text-[10px] text-slate-500 font-semibold rounded">Semana Laboral en Curso</span></div><div class="h-72">@if($history->isEmpty())<div class="h-full flex items-center justify-center font-mono text-gray-400 text-xs">Sin registros suficientes para graficar.</div>@else<svg class="h-60 w-full" viewBox="0 0 900 240" preserveAspectRatio="none">@foreach([50,100,150,200] as $y)<line x1="0" y1="{{ $y }}" x2="900" y2="{{ $y }}" stroke="#f1f5f9" stroke-dasharray="3 3"/>@endforeach<polyline points="{{ $line('total_active_seconds') }}" fill="none" stroke="#4f46e5" stroke-width="3"/><polyline points="{{ $line('total_idle_seconds') }}" fill="none" stroke="#f59e0b" stroke-width="2" stroke-dasharray="4 4"/></svg><div class="flex justify-between text-[10px] text-slate-400 font-mono">@foreach($history as $s)<span>{{ $s->summary_date }}</span>@endforeach</div><div class="flex justify-center gap-4 mt-3 text-[10px]"><span class="text-indigo-600">● Tiempo Activo (Horas)</span><span class="text-amber-500">● Tiempo Inactivo (Horas)</span></div>@endif</div></section><div class="grid grid-cols-1 md:grid-cols-2 gap-8"><section class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm"><h3 class="text-sm font-bold text-gray-900">Distribución de Foco por Aplicación</h3><p class="text-xs text-gray-500 mt-0.5">Proporción de horas dedicadas por herramienta principal</p><div class="mt-6 space-y-3">@forelse($appTotals as $name=>$seconds)<div class="flex justify-between text-xs"><span class="font-medium text-gray-700">● {{ $name }}</span><span class="font-mono text-gray-500 font-bold">{{ number_format($seconds/3600,1) }}h</span></div>@empty<p class="text-xs text-gray-400">Sin aplicaciones suficientes.</p>@endforelse</div></section><section class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm"><h3 class="text-sm font-bold text-gray-900">Top Dominios Visitados (Web Tracker)</h3><p class="text-xs text-gray-500 mt-0.5">Horas acumuladas dedicadas a portales web</p><div class="space-y-4 my-5">@forelse($domainTotals as $name=>$seconds)<div class="space-y-1.5 text-xs"><div class="flex justify-between"><span class="font-mono text-slate-800 font-semibold">{{ $name }}</span><span class="font-mono text-gray-500 font-bold">{{ number_format($seconds/3600,1) }}h</span></div><div class="w-full bg-slate-100 rounded-full h-2"><div class="bg-indigo-600 h-2 rounded-full" style="width:{{ max(3,$seconds/$domainMax*100) }}%"></div></div></div>@empty<p class="text-xs text-gray-400">Sin dominios suficientes.</p>@endforelse</div></section></div></div>
@elseif($tab==='events')
@php
    $eventSeconds = $events->sum('duration');
    $activeEvents = $events->where('event_type', 'active')->count();
    $incidents = $events->whereIn('event_type', ['blocked', 'blocked-site', 'locked'])->count();
    $groupBy = request('group_by', 'none');
    $groupOptions = ['none' => 'Sin agrupar', 'application' => 'Aplicación', 'domain' => 'Dominio', 'type' => 'Estado', 'day' => 'Día'];
    if (!array_key_exists($groupBy, $groupOptions)) $groupBy = 'none';
    $eventGroups = $groupBy === 'none' ? collect(['Todos los eventos' => $events]) : $events->groupBy(fn($event) => match($groupBy) {
        'application' => $event->app ?: 'Sin aplicación',
        'domain' => $event->domain ?: 'Sin dominio',
        'type' => $event->event_type ?: 'Sin estado',
        'day' => $event->display_timestamp->format('d/m/Y'),
    });
    $hasFilters = collect(['date_from','date_to','app','domain','event_type','event_search'])->contains(fn($key) => filled(request($key))) || request()->boolean('blocked_only');
    $eventTypeClass = fn($type) => match($type) {
        'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'idle' => 'bg-amber-50 text-amber-700 ring-amber-100',
        'locked' => 'bg-violet-50 text-violet-700 ring-violet-100',
        'blocked', 'blocked-site' => 'bg-rose-50 text-rose-700 ring-rose-100',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
@endphp
<section class="timeline-panel overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="timeline-panel-header border-b border-slate-100 bg-gradient-to-r from-indigo-50/70 via-white to-white">
        <div class="timeline-summary">
            <div class="timeline-summary-info">
                <div class="timeline-heading"><span class="timeline-heading-icon">⌁</span><div class="timeline-heading-copy"><span>Monitor de productividad</span><h3>Timeline de actividad</h3></div></div>
                <p class="mt-3 text-xs leading-relaxed text-slate-500"><b class="font-mono text-slate-700">{{ number_format($eventTotal) }}</b> señales registradas. Explora el historial, filtra por contexto y concentra la revisión en lo que importa.</p>
            </div>
            <div class="timeline-summary-cards">
                <div class="timeline-summary-card border-indigo-100"><span>Mostrados</span><b class="text-slate-800">{{ $events->count() }}</b></div>
                <div class="timeline-summary-card border-emerald-100"><span>Tiempo</span><b class="text-emerald-600">{{ $fmt($eventSeconds) }}</b></div>
                <div class="timeline-summary-card {{ $incidents ? 'border-rose-100' : 'border-slate-100' }}"><span>Alertas</span><b class="{{ $incidents ? 'text-rose-600' : 'text-slate-500' }}">{{ $incidents }}</b></div>
            </div>
        </div>
    </div>

    <div class="timeline-panel-body">
        <div class="mb-4 flex flex-col gap-3 rounded-xl border border-slate-100 bg-slate-50/70 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-2">
                <span class="mr-1 font-mono text-[9px] font-bold uppercase tracking-wider text-slate-400">Periodo rápido</span>
                <button type="button" data-range="today" class="event-range rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-600 transition hover:border-indigo-300 hover:text-indigo-700">Hoy</button>
                <button type="button" data-range="week" class="event-range rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-600 transition hover:border-indigo-300 hover:text-indigo-700">Últimos 7 días</button>
                <button type="button" data-range="month" class="event-range rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-600 transition hover:border-indigo-300 hover:text-indigo-700">Últimos 30 días</button>
            </div>
            @if($hasFilters)<a href="{{ route('employees.show',$employee->id) }}?tab=events" class="inline-flex items-center justify-center gap-1.5 text-xs font-bold text-slate-500 hover:text-rose-600">× Limpiar filtros</a>@endif
        </div>

        <form id="event-filters" method="get" class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
            <input type="hidden" name="tab" value="events">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-7">
                <label class="font-mono text-[9px] font-bold uppercase tracking-wider text-slate-400">Desde<input id="event-date-from" type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold normal-case tracking-normal text-slate-700 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"></label>
                <label class="font-mono text-[9px] font-bold uppercase tracking-wider text-slate-400">Hasta<input id="event-date-to" type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold normal-case tracking-normal text-slate-700 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"></label>
                <label class="font-mono text-[9px] font-bold uppercase tracking-wider text-slate-400">Estado<select name="event_type" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold normal-case tracking-normal text-slate-700 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"><option value="all">Todos</option>@foreach(['active'=>'Activo','idle'=>'Inactivo','locked'=>'Bloqueado','startup'=>'Inicio','shutdown'=>'Apagado','blocked'=>'Sitio bloqueado'] as $value=>$label)<option value="{{ $value }}" @selected(request('event_type')===$value)>{{ $label }}</option>@endforeach</select></label>
                <label class="font-mono text-[9px] font-bold uppercase tracking-wider text-slate-400">Agrupar por<select name="group_by" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold normal-case tracking-normal text-slate-700 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">@foreach($groupOptions as $value=>$label)<option value="{{ $value }}" @selected($groupBy===$value)>{{ $label }}</option>@endforeach</select></label>
                <label class="font-mono text-[9px] font-bold uppercase tracking-wider text-slate-400">Aplicación<input type="text" name="app" value="{{ request('app') }}" placeholder="Ej. Brave" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold normal-case tracking-normal text-slate-700 placeholder:text-slate-300 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"></label>
                <label class="font-mono text-[9px] font-bold uppercase tracking-wider text-slate-400">Dominio<input type="text" name="domain" value="{{ request('domain') }}" placeholder="Ej. chatgpt.com" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold normal-case tracking-normal text-slate-700 placeholder:text-slate-300 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"></label>
                <label class="font-mono text-[9px] font-bold uppercase tracking-wider text-slate-400">Buscar<input type="search" name="event_search" value="{{ request('event_search') }}" placeholder="Título o detalle" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold normal-case tracking-normal text-slate-700 placeholder:text-slate-300 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"></label>
            </div>
            <div class="mt-4 flex flex-col gap-3 border-t border-slate-200 pt-3 sm:flex-row sm:items-center sm:justify-between"><div class="flex flex-wrap items-center gap-4"><label class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-600"><input type="checkbox" name="blocked_only" value="1" @checked(request()->boolean('blocked_only')) class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Sólo incidencias y bloqueos</label><label class="inline-flex items-center gap-2 font-mono text-[10px] font-bold uppercase tracking-wider text-slate-400">Por página<select name="per_page" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[11px] font-bold normal-case tracking-normal text-slate-700 outline-none focus:border-indigo-400">@foreach([50,100,250,500,1000] as $size)<option value="{{ $size }}" @selected((int)request('per_page',100)===$size)>{{ number_format($size) }}</option>@endforeach</select></label></div><button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-black text-white shadow-sm shadow-indigo-200 transition hover:bg-indigo-500">Aplicar filtros <span class="ml-1 opacity-75">→</span></button></div>
        </form>

        <div class="mt-5 overflow-hidden rounded-xl border border-slate-100">
            <div class="overflow-x-auto"><table class="min-w-[880px] w-full text-left text-xs"><thead><tr class="border-b border-slate-100 bg-slate-50/80 font-mono text-[9px] font-bold uppercase tracking-wider text-slate-400"><th class="px-4 py-3">Momento</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Aplicación</th><th class="px-4 py-3">Dominio</th><th class="px-4 py-3">Actividad detectada</th><th class="px-4 py-3 text-right">Duración</th></tr></thead>
            @forelse($eventGroups as $groupName=>$groupEvents)
                @php
                    $groupOpen = $groupBy === 'none' || $loop->first;
                @endphp
                <tbody data-event-group class="divide-y divide-slate-100 bg-white">
                @if($groupBy !== 'none')
                    <tr class="bg-indigo-50/70"><td colspan="6" class="p-0"><button type="button" class="event-group-toggle flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition hover:bg-indigo-100/70" aria-expanded="{{ $groupOpen ? 'true' : 'false' }}"><span class="flex items-center gap-2"><span class="event-group-icon flex h-5 w-5 items-center justify-center rounded-md bg-white font-mono text-xs font-black text-indigo-600 shadow-sm">{{ $groupOpen ? '−' : '+' }}</span><span class="font-mono text-[10px] font-black uppercase tracking-wider text-indigo-700">{{ $groupOptions[$groupBy] }} · {{ $groupName }}</span></span><span class="font-mono text-[10px] font-bold text-indigo-500">{{ $groupEvents->count() }} eventos · {{ $fmt($groupEvents->sum('duration')) }}</span></button></td></tr>
                @endif
                @foreach($groupEvents as $event)
                    <tr class="event-group-row group transition hover:bg-indigo-50/40 {{ $groupOpen ? '' : 'hidden' }}"><td class="whitespace-nowrap px-4 py-3"><span class="block font-mono text-[11px] font-bold text-slate-700">{{ $event->display_timestamp->format('d/m/Y') }}</span><span class="mt-0.5 block font-mono text-[10px] text-slate-400">{{ $event->display_timestamp->format('H:i:s') }}</span></td><td class="px-4 py-3"><span class="inline-flex rounded-full px-2 py-1 font-mono text-[9px] font-bold ring-1 ring-inset {{ $eventTypeClass($event->event_type) }}">{{ $event->event_type }}</span></td><td class="px-4 py-3 font-semibold text-slate-800">{{ $event->app ?: '—' }}</td><td class="max-w-[170px] px-4 py-3 font-mono text-[11px] text-indigo-600"><span class="block truncate" title="{{ $event->domain }}">{{ $event->domain ?: '—' }}</span></td><td class="max-w-[320px] px-4 py-3 text-slate-500"><span class="block truncate" title="{{ $event->title }}">{{ $event->title ?: 'Sin título reportado' }}</span></td><td class="whitespace-nowrap px-4 py-3 text-right font-mono font-bold text-slate-700">{{ $fmt((int) $event->duration) }}</td></tr>
                @endforeach
                </tbody>
            @empty
                <tbody class="bg-white"><tr><td colspan="6" class="px-4 py-16 text-center"><span class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-xl text-slate-400">⌁</span><b class="mt-3 block text-sm text-slate-700">No hay actividad con estos filtros</b><p class="mt-1 text-xs text-slate-400">Prueba otro periodo o elimina los filtros activos.</p></td></tr></tbody>
            @endforelse
            </table></div>
        </div>
        @if($eventsPaginator && $eventsPaginator->hasPages())<div class="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between"><span class="font-mono text-[10px] text-slate-400">Página {{ $eventsPaginator->currentPage() }} de {{ $eventsPaginator->lastPage() }} · {{ number_format($eventsPaginator->total()) }} eventos</span><div class="flex items-center gap-2">@if($eventsPaginator->onFirstPage())<span class="rounded-lg border border-slate-100 px-3 py-2 text-xs font-bold text-slate-300">← Anterior</span>@else<a href="{{ $eventsPaginator->previousPageUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-indigo-300 hover:text-indigo-700">← Anterior</a>@endif @if($eventsPaginator->hasMorePages())<a href="{{ $eventsPaginator->nextPageUrl() }}" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-500">Siguiente →</a>@else<span class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold text-slate-400">Siguiente →</span>@endif</div></div>@endif
        @if($events->isNotEmpty())<p class="mt-3 text-right font-mono text-[10px] text-slate-400">{{ $activeEvents }} eventos activos · últimos {{ $events->count() }} registros mostrados</p>@endif
    </div>
</section>
@elseif($tab==='apps')
@php
    $appIcon = function ($name, bool $domain = false): array {
        $value = strtolower((string) $name);
        if ($domain) return match (true) {
            str_contains($value, 'github') => ['fa-brands fa-github', 'insight-icon-github'],
            str_contains($value, 'facebook') => ['fa-brands fa-facebook-f', 'insight-icon-facebook'],
            str_contains($value, 'instagram') => ['fa-brands fa-instagram', 'insight-icon-instagram'],
            str_contains($value, 'hubspot') => ['fa-brands fa-hubspot', 'insight-icon-hubspot'],
            str_contains($value, 'chatgpt') || str_contains($value, 'openai') => ['fa-solid fa-wand-magic-sparkles', 'insight-icon-openai'],
            default => ['fa-solid fa-globe', 'insight-icon-domain'],
        };
        return match (true) {
            str_contains($value, 'brave') => ['fa-solid fa-shield-halved', 'insight-icon-brave'],
            str_contains($value, 'chatgpt') || str_contains($value, 'openai') => ['fa-solid fa-wand-magic-sparkles', 'insight-icon-openai'],
            str_contains($value, 'slack') => ['fa-brands fa-slack', 'insight-icon-slack'],
            str_contains($value, 'opera') => ['fa-brands fa-opera', 'insight-icon-opera'],
            str_contains($value, 'whatsapp') => ['fa-brands fa-whatsapp', 'insight-icon-whatsapp'],
            str_contains($value, 'spotify') => ['fa-brands fa-spotify', 'insight-icon-spotify'],
            str_contains($value, 'explorer') => ['fa-solid fa-folder-tree', 'insight-icon-explorer'],
            str_contains($value, 'tracker') => ['fa-solid fa-chart-line', 'insight-icon-tracker'],
            default => ['fa-solid fa-cube', 'insight-icon-default'],
        };
    };
    $insightTotal = $appTotals->sum() + $domainTotals->sum();
@endphp
<div class="insight-page">
    <section class="insight-hero">
        <div><span class="insight-kicker"><i class="fa-solid fa-chart-pie"></i> Inteligencia de actividad</span><h3>Apps y sitios con mayor foco</h3><p>Una lectura visual de las herramientas y portales que concentraron tiempo durante el periodo consultado.</p></div>
        <div class="insight-hero-metrics"><div><i class="fa-solid fa-cubes-stacked"></i><span>Apps</span><b>{{ $appTotals->count() }}</b></div><div><i class="fa-solid fa-earth-americas"></i><span>Dominios</span><b>{{ $domainTotals->count() }}</b></div><div><i class="fa-regular fa-clock"></i><span>Tiempo visible</span><b>{{ $fmt($insightTotal) }}</b></div></div>
    </section>
    <div class="insight-grid">
        @foreach([['title'=>'Herramientas más utilizadas','subtitle'=>'Aplicaciones detectadas por el agente','items'=>$appTotals,'max'=>$appMax,'domain'=>false,'icon'=>'fa-solid fa-laptop-code'],['title'=>'Direcciones web visitadas','subtitle'=>'Dominios identificados durante la navegación','items'=>$domainTotals,'max'=>$domainMax,'domain'=>true,'icon'=>'fa-solid fa-globe']] as $panel)
        <section class="insight-card">
            <header><div class="insight-card-title"><span><i class="{{ $panel['icon'] }}"></i></span><div><h4>{{ $panel['title'] }}</h4><p>{{ $panel['subtitle'] }}</p></div></div><span class="insight-count">{{ $panel['items']->count() }} registros</span></header>
            <div class="insight-list">
                @forelse($panel['items'] as $name=>$seconds)
                @php
                    [$iconClass, $iconTheme] = $appIcon($name, $panel['domain']);
                    $percentage = min(100, max(3, ($seconds / $panel['max']) * 100));
                @endphp
                <article class="insight-row">
                    <span class="insight-rank">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="insight-app-icon {{ $iconTheme }}"><i class="{{ $iconClass }}"></i></span>
                    <div class="insight-row-main"><div class="insight-row-label"><strong title="{{ $name }}">{{ $name }}</strong><span>{{ number_format($seconds / 3600, 1) }} h · {{ number_format(($seconds / $panel['max']) * 100, 0) }}%</span></div><div class="insight-progress"><i style="width: {{ $percentage }}%"></i></div></div>
                    <b class="insight-time">{{ $fmt($seconds) }}</b>
                </article>
                @empty
                <div class="insight-empty"><i class="fa-regular fa-folder-open"></i><b>Sin datos suficientes</b><p>El agente aún no ha reportado actividad para este periodo.</p></div>
                @endforelse
            </div>
        </section>
        @endforeach
    </div>
</div>
@else
@if(session('success'))<div class="mb-6 flex items-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700">✓ {{ session('success') }}</div>@endif
<section class="max-w-5xl rounded-2xl border border-slate-100 bg-white p-6 shadow-sm"><div class="mb-6 border-b border-slate-100 pb-4"><h3 class="text-sm font-bold text-slate-900">Especificaciones Técnicas del Equipo</h3><p class="mt-1 text-xs text-slate-500">Ficha de hardware y conectividad recopilada por el Agente Cliente.</p></div>
@forelse($devices as $device)
<form method="post" action="{{ route('employees.devices.update',[$employee->id,$device->id]) }}" class="{{ !$loop->first ? 'mt-8 border-t border-slate-100 pt-8' : '' }}">@csrf @method('PUT')
    <div class="mb-4 flex flex-col justify-between gap-2 sm:flex-row sm:items-center"><span class="font-mono text-[10px] font-bold uppercase tracking-wider text-slate-400">DISPOSITIVO {{ $loop->iteration }} · {{ $device->hostname ?: 'Equipo sin hostname' }}</span><span class="font-mono text-[10px] text-slate-400">Última sincronización: {{ $device->last_sync ? \Carbon\Carbon::parse($device->last_sync, 'UTC')->setTimezone($corporateTimezone)->format('d/m/Y H:i') : 'Pendiente' }}</span></div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">@foreach(['brand'=>'Marca / Fabricante','model'=>'Modelo del equipo','processor'=>'Procesador','ram'=>'Memoria RAM','storage'=>'Almacenamiento','serial_number'=>'Número de serie','os'=>'Sistema operativo'] as $field=>$label)<label class="rounded-xl border border-slate-100 bg-slate-50 p-4 font-mono text-[10px] font-bold uppercase text-slate-400">{{ $label }}<input name="{{ $field }}" value="{{ old($field,$device->{$field}) }}" {{ $field==='os' ? 'required' : '' }} class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold normal-case text-slate-800 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"></label>@endforeach</div>
    <div class="mt-4 grid grid-cols-1 gap-3 rounded-xl border border-slate-800 bg-slate-900 p-4 text-[10px] font-mono sm:grid-cols-3"><div><span class="block font-bold uppercase text-slate-500">Dirección IP local</span><b class="mt-1 block text-slate-200">{{ $device->ip ?: 'No reportada' }}</b></div><div><span class="block font-bold uppercase text-slate-500">Versión del agente</span><b class="mt-1 block text-indigo-300">{{ $device->version ?: 'No reportada' }}</b></div><div><span class="block font-bold uppercase text-slate-500">Uso de disco</span><b class="mt-1 block text-slate-200">{{ $device->disk_used_percent !== null ? $device->disk_used_percent.'%' : 'No reportado' }}</b></div></div>
    <button class="mt-4 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-500">Guardar dispositivo</button>
</form>
@empty
<div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center"><div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-2xl text-indigo-600">▱</div><h4 class="font-mono text-xs font-black uppercase tracking-widest text-slate-800">Esperando Conexión del Agente…</h4><p class="mx-auto mt-2.5 max-w-md text-xs leading-relaxed text-slate-500">Los detalles de hardware se cargarán automáticamente al vincular la aplicación cliente con la KEY única del colaborador.</p>@if($employee->client_key)<div class="mx-auto mt-6 max-w-sm rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm"><span class="mb-1 block font-mono text-[9px] font-bold uppercase tracking-wider text-slate-400">Clave de vinculación cliente</span><div class="flex items-center gap-2 rounded-xl border border-slate-100 bg-slate-50 p-2"><code id="employee-client-key" class="min-w-0 flex-1 select-all truncate font-mono text-xs font-black tracking-wide text-indigo-600">{{ $employee->client_key }}</code><button type="button" id="copy-employee-key" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-[10px] font-bold text-slate-500">Copiar</button></div></div>@endif</div>
@endforelse
</section>
@endif
</div></main></div>
@if($tab==='overview')
<script>window.workLiveEmployeeInsights={history:{!! json_encode($history->map(function($item){return ['date'=>$item->summary_date,'active'=>round($item->total_active_seconds/3600,1),'idle'=>round($item->total_idle_seconds/3600,1)];})->values()) !!},apps:{!! json_encode($appTotals->map(fn($seconds,$name)=>['name'=>$name,'hours'=>round($seconds/3600,2)])->values()) !!},domains:{!! json_encode($domainTotals->map(fn($seconds,$name)=>['name'=>$name,'hours'=>round($seconds/3600,2)])->values()) !!}};</script>
@vite('resources/js/employee-profile.js')
@endif
@if($tab==='device')<script>document.getElementById('copy-employee-key')?.addEventListener('click',async()=>{const key=document.getElementById('employee-client-key')?.textContent?.trim();if(key){await navigator.clipboard.writeText(key);document.getElementById('copy-employee-key').textContent='Copiada';}});</script>@endif
@if($tab==='events')
<script>
(() => {
    const form = document.getElementById('event-filters');
    const from = document.getElementById('event-date-from');
    const to = document.getElementById('event-date-to');
    const dateValue = (date) => {
        const offset = date.getTimezoneOffset() * 60000;
        return new Date(date.getTime() - offset).toISOString().slice(0, 10);
    };
    document.querySelectorAll('.event-range').forEach((button) => button.addEventListener('click', () => {
        const end = new Date();
        const start = new Date(end);
        if (button.dataset.range === 'week') start.setDate(end.getDate() - 6);
        if (button.dataset.range === 'month') start.setDate(end.getDate() - 29);
        from.value = dateValue(start);
        to.value = dateValue(end);
        form.requestSubmit();
    }));
    const setGroup = (tbody, open) => {
        tbody.querySelectorAll('.event-group-row').forEach((row) => row.classList.toggle('hidden', !open));
        const button = tbody.querySelector('.event-group-toggle');
        if (!button) return;
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        const icon = button.querySelector('.event-group-icon');
        if (icon) icon.textContent = open ? '−' : '+';
    };
    document.querySelectorAll('.event-group-toggle').forEach((button) => button.addEventListener('click', () => {
        const group = button.closest('[data-event-group]');
        const open = button.getAttribute('aria-expanded') !== 'true';
        document.querySelectorAll('[data-event-group]').forEach((item) => { if (item !== group) setGroup(item, false); });
        setGroup(group, open);
    }));
})();
</script>
@endif
@endsection
