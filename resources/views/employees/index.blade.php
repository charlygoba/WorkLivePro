@extends('layouts.app')

@section('body')
@php
    $statusClass = fn ($status) => match ($status) {
        'active' => 'bg-emerald-500 text-white', 'online' => 'bg-emerald-100 text-emerald-700',
        'idle' => 'bg-amber-100 text-amber-700', 'locked' => 'bg-indigo-100 text-indigo-700',
        default => 'bg-slate-100 text-slate-600',
    };
    $statusLabel = fn ($status) => match ($status) {
        'active' => 'Activo (Live)', 'online' => 'Online (Disp)', 'idle' => 'Inactivo (Idle)',
        'locked' => 'Bloqueado', default => 'Offline',
    };
    $statusIcon = fn ($status) => match ($status) {
        'active', 'online' => 'fa-solid fa-circle-check', 'idle' => 'fa-solid fa-mug-hot',
        'locked' => 'fa-solid fa-lock', default => 'fa-solid fa-circle-minus',
    };
    $formatSeconds = fn ($seconds) => floor($seconds / 3600).'h '.round(($seconds % 3600) / 60).'m';
    $isAdmin = filled(session('worklive_admin.email'));
    $availableCount = $employees->whereIn('status', ['active', 'online'])->count();
    $offlineCount = $employees->where('status', 'offline')->count();
    $sortLink = function (string $key) use ($sort, $direction) {
        $nextDirection = $sort === $key && $direction === 'asc' ? 'desc' : 'asc';
        return route('employees', array_merge(request()->query(), ['sort' => $key, 'direction' => $nextDirection]));
    };
    $sortIcon = fn (string $key) => $sort !== $key ? 'fa-solid fa-sort text-slate-300' : ($direction === 'asc' ? 'fa-solid fa-sort-up text-indigo-500' : 'fa-solid fa-sort-down text-indigo-500');
@endphp

<div class="flex min-h-screen overflow-hidden bg-slate-100/45 font-sans text-slate-800">
    @include('partials.sidebar')
    <main class="flex h-screen min-w-0 flex-1 flex-col overflow-hidden bg-slate-50/20">
        @include('partials.header',['title'=>'Nómina de Empleados'])
        <div class="flex-1 overflow-y-auto bg-slate-50/60 p-6 lg:p-8">
            <section class="app-section-hero mb-6"><div class="app-section-hero-copy"><p><i class="fa-solid fa-users-viewfinder" aria-hidden="true"></i> Operación de equipo</p><h2>{{ $workliveBrand?->menu_employees ?: 'Lista de Empleados' }}</h2><span>Consulta estados, actividad y vínculo de cada colaborador en tiempo real.</span></div><div class="app-section-hero-metrics"><div><i class="fa-solid fa-users" aria-hidden="true"></i><small>Equipo</small><b>{{ $employees->count() }}</b></div><div><i class="fa-solid fa-signal" aria-hidden="true"></i><small>Disponibles</small><b>{{ $availableCount }}</b></div><div><i class="fa-solid fa-power-off" aria-hidden="true"></i><small>Offline</small><b>{{ $offlineCount }}</b></div></div><div class="app-section-hero-actions"><a href="{{ route('employees', request()->query()) }}"><i class="fa-solid fa-rotate-right" aria-hidden="true"></i><span>Refrescar</span></a><button type="button" onclick="window.openEmployeeCrud?.()"><i class="fa-solid fa-user-plus" aria-hidden="true"></i><span>Nuevo empleado</span></button></div></section>

            @if(session('success'))
                <div class="mb-5 flex items-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-xs font-semibold text-emerald-800"><i class="fa-solid fa-circle-check text-emerald-500" aria-hidden="true"></i>{{ session('success') }}</div>
            @endif

            <form method="get" class="mb-6 rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between"><p class="flex items-center gap-2 text-xs font-bold text-slate-800"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"><i class="fa-solid fa-sliders" aria-hidden="true"></i></span> Filtros de colaboradores</p><span class="hidden font-mono text-[10px] text-slate-400 sm:block">Busca y segmenta la operación</span></div>
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-4 lg:items-end">
                    <label class="block"><span class="mb-1.5 block font-mono text-[9px] font-bold uppercase tracking-wider text-slate-400">Búsqueda</span><span class="relative block"><i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400" aria-hidden="true"></i><input type="search" name="search" value="{{ request('search') }}" placeholder="Colaborador, país, área o KEY..." class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-3 text-xs font-semibold outline-none transition focus:border-indigo-400 focus:bg-white"></span></label>
                    <label class="block"><span class="mb-1.5 block font-mono text-[9px] font-bold uppercase tracking-wider text-slate-400">Departamento</span><span class="relative block"><i class="fa-solid fa-building absolute left-3.5 top-3 text-slate-400" aria-hidden="true"></i><select name="department" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-8 text-xs font-semibold text-slate-700 outline-none focus:border-indigo-400"><option value="All">Todos los departamentos</option>@foreach($departments as $department)<option value="{{ $department }}" @selected(request('department') === $department)>{{ $department }}</option>@endforeach</select></span></label>
                    <label class="block"><span class="mb-1.5 block font-mono text-[9px] font-bold uppercase tracking-wider text-slate-400">Estado</span><span class="relative block"><i class="fa-solid fa-signal absolute left-3.5 top-3 text-slate-400" aria-hidden="true"></i><select name="status" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-8 text-xs font-semibold text-slate-700 outline-none focus:border-indigo-400"><option value="All">Todos los estados</option>@foreach(['offline','online','active','idle','locked'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $statusLabel($status) }}</option>@endforeach</select></span></label>
                    <div class="flex gap-2"><a href="{{ route('employees') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-3 py-2.5 text-xs text-slate-500 transition hover:text-rose-600" title="Limpiar filtros"><i class="fa-solid fa-filter-circle-xmark" aria-hidden="true"></i></a><button class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-slate-700"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filtrar</button></div>
                </div>
            </form>

            <section class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 bg-gradient-to-r from-white to-indigo-50/40 px-5 py-4"><div><p class="text-sm font-black text-slate-900"><i class="fa-solid fa-list-check mr-2 text-indigo-500" aria-hidden="true"></i>{{ $employees->count() }} colaboradores mostrados</p><p class="mt-0.5 text-[11px] text-slate-400">Actividad, estado y vínculo del agente por persona.</p></div><p class="hidden rounded-full bg-white px-3 py-1 font-mono text-[10px] text-slate-500 shadow-sm md:block"><i class="fa-regular fa-clock mr-1 text-indigo-500" aria-hidden="true"></i>Zona corporativa</p></div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1380px] text-left text-[11px]">
                        <thead class="border-b border-slate-100 bg-slate-50/70 font-mono text-[10px] font-bold uppercase tracking-wider text-slate-400"><tr>@foreach(['name'=>'Empleado','department'=>'Departamento','country'=>'País y zona','status'=>'Estado','app'=>'Aplicación foco','domain'=>'Dominio activo','last_active'=>'Última actividad'] as $key => $label)<th class="px-4 py-3.5 first:px-5"><a href="{{ $sortLink($key) }}" class="inline-flex items-center gap-1.5 transition hover:text-indigo-600">{{ $label }}<i class="{{ $sortIcon($key) }}" aria-hidden="true"></i></a></th>@endforeach<th class="px-4 py-3.5 text-right"><a href="{{ $sortLink('time_today') }}" class="inline-flex items-center gap-1.5 transition hover:text-indigo-600">Tiempos hoy<i class="{{ $sortIcon('time_today') }}" aria-hidden="true"></i></a></th><th class="px-5 py-3.5 text-center">Acciones</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($employees as $employee)
                                <tr class="transition hover:bg-indigo-50/30">
                                    <td class="px-5 py-3.5"><div class="flex items-center gap-4"><div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-indigo-100 bg-indigo-50 text-[10px] font-black uppercase text-indigo-700">{{ collect(explode(' ', $employee->name))->filter()->take(2)->map(fn($name) => mb_substr($name,0,1))->join('') }}</div><div class="min-w-0"><a href="{{ route('employees.show',$employee->id) }}" class="block truncate font-bold text-slate-950 transition hover:text-indigo-600">{{ $employee->name }}</a>@if($employee->client_key)<button type="button" data-copy="{{ $employee->client_key }}" class="copy-key mt-1 inline-flex items-center gap-1 rounded-md bg-indigo-50 px-1.5 py-0.5 font-mono text-[9px] font-bold text-indigo-600 transition hover:bg-indigo-100"><i class="fa-solid fa-key" aria-hidden="true"></i><span>{{ $employee->client_key }}</span></button>@endif</div></div></td>
                                    <td class="px-4 py-3.5 font-semibold text-slate-800">{{ $employee->department }}</td>
                                    <td class="px-4 py-3.5"><span class="block font-medium text-slate-700"><i class="fa-solid fa-location-dot mr-1 text-slate-400" aria-hidden="true"></i>{{ $employee->country }}</span><span class="mt-0.5 block font-mono text-[9px] text-slate-400">{{ $employee->timezone }}</span></td>
                                    <td class="px-4 py-3.5"><span class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 font-mono text-[8px] font-bold uppercase {{ $statusClass($employee->status) }}"><i class="{{ $statusIcon($employee->status) }}" aria-hidden="true"></i>{{ $statusLabel($employee->status) }}</span></td>
                                    <td class="px-4 py-3.5"><span class="block max-w-44 truncate font-semibold text-slate-900">{{ $employee->current_app ?: 'Sin actividad' }}</span><span class="mt-0.5 block max-w-44 truncate text-[9px] text-slate-400">{{ $employee->current_title ?: 'Sin actividad en primer plano' }}</span></td>
                                    <td class="px-4 py-3.5 font-mono font-semibold text-indigo-600">@if($employee->current_domain && $employee->current_domain !== 'none')<i class="fa-solid fa-globe mr-1 text-indigo-400" aria-hidden="true"></i>{{ $employee->current_domain }}@else <span class="text-slate-300">—</span> @endif</td>
                                    <td class="px-4 py-3.5 font-mono text-slate-500">@if($employee->last_active_display)<span class="block font-semibold text-slate-700">{{ $employee->last_active_display->locale('es')->isoFormat('D MMM') }}</span><span class="mt-0.5 text-[9px] text-slate-400" title="Dato recibido en UTC: {{ $employee->last_active }}"><i class="fa-regular fa-clock mr-1" aria-hidden="true"></i>{{ $employee->last_active_display->format('H:i') }}</span>@else <span class="text-slate-300">—</span> @endif</td>
                                    <td class="px-4 py-3.5 text-right font-mono">@if(($employee->active_time_today ?: 0) > 0 || ($employee->idle_time_today ?: 0) > 0)<span class="block font-bold text-emerald-600"><i class="fa-solid fa-arrow-trend-up mr-1" aria-hidden="true"></i>{{ $formatSeconds($employee->active_time_today ?: 0) }}</span><span class="mt-0.5 block text-[9px] text-amber-500"><i class="fa-solid fa-hourglass-half mr-1" aria-hidden="true"></i>{{ $formatSeconds($employee->idle_time_today ?: 0) }}</span>@else <span class="text-slate-300">—</span> @endif</td>
                                    <td class="px-5 py-3.5"><div class="flex justify-center gap-1.5"><a href="{{ route('employees.show',$employee->id) }}" title="Abrir expediente" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700"><i class="fa-regular fa-id-card" aria-hidden="true"></i></a><a href="{{ route('employees.show',$employee->id) }}?tab=overview&amp;edit=1" title="Editar empleado" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-100 bg-blue-50 text-blue-700 transition hover:bg-blue-100"><i class="fa-solid fa-pen" aria-hidden="true"></i></a><form method="post" action="{{ route('employees.sync',$employee->id) }}">@csrf<button type="submit" title="Solicitar actualización al cliente" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-100 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100"><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i></button></form></div></td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-5 py-14 text-center"><i class="fa-solid fa-users-slash mb-3 block text-2xl text-slate-300" aria-hidden="true"></i><p class="font-mono text-xs text-slate-400">No se encontraron empleados con esos filtros.</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>

@include('employees.crud-modal')
<script>
    document.querySelectorAll('.copy-key').forEach((button) => button.addEventListener('click', () => navigator.clipboard?.writeText(button.dataset.copy).then(() => {
        const label = button.querySelector('span'); const original = label.textContent; label.textContent = 'KEY copiada'; setTimeout(() => label.textContent = original, 1500);
    })));
    document.querySelectorAll('a[href*="edit=1"]').forEach((link) => link.addEventListener('click', (event) => {
        event.preventDefault();
        const row = link.closest('tr'), cells = row.querySelectorAll('td'), profile = row.querySelector('a[href*="/employees/"]');
        const employeeId = profile?.getAttribute('href').match(/employees\/([^?]+)/)?.[1];
        if (!employeeId) return;
        window.openEmployeeCrud?.({
            id: employeeId,
            name: cells[0].querySelector('a')?.textContent.trim(),
            department: cells[1].textContent.trim(),
            country: cells[2].querySelector('span')?.textContent.trim(),
            timezone: cells[2].querySelectorAll('span')[1]?.textContent.trim(),
            status: cells[3].textContent.toLowerCase().includes('activo') ? 'active' : (cells[3].textContent.toLowerCase().includes('online') ? 'online' : (cells[3].textContent.toLowerCase().includes('inactivo') ? 'idle' : (cells[3].textContent.toLowerCase().includes('bloqueado') ? 'locked' : 'offline'))),
            key: cells[0].querySelector('[data-copy]')?.dataset.copy,
        });
    }));
</script>
@endsection
