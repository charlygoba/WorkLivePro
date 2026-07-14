@php
    $brand = $workliveBrand ?? null;
    $menu = [
        'dashboard' => $brand?->menu_dashboard ?: 'Tablero Principal', 'employees' => $brand?->menu_employees ?: 'Lista de Empleados', 'reports' => $brand?->menu_reports ?: 'Reportes y Exportación',
        'policies' => $brand?->menu_policies ?: 'Políticas de Uso', 'time_clock' => $brand?->menu_time_clock ?: 'RH · Reloj Checador', 'settings' => $brand?->menu_settings ?: 'Configuración',
        'system' => $brand?->menu_system ?: 'Sistema', 'administrators' => $brand?->menu_administrators ?: 'Administradores', 'personalization' => $brand?->menu_personalization ?: 'Personalización',
    ];
@endphp
<aside class="worklive-sidebar w-68 flex h-screen shrink-0 flex-col overflow-y-auto border-r border-slate-800 bg-slate-900 text-slate-100 lg:sticky lg:top-0 lg:self-start">
    <div class="flex items-center space-x-3 border-b border-slate-800 p-6">
        @php($logoBackgroundClass = ($brand?->logo_background_enabled ?? true) ? 'worklive-brand-icon--filled' : 'worklive-brand-icon--transparent')
        @if(!empty($brand?->brand_icon_path))<img class="worklive-brand-icon {{ $logoBackgroundClass }} h-8 w-8 rounded object-cover shadow-md" src="{{ asset('storage/'.$brand->brand_icon_path) }}" alt="Ícono del sistema">@else<div class="worklive-brand-icon {{ $logoBackgroundClass }} flex h-8 w-8 items-center justify-center rounded bg-indigo-600 text-white shadow-md"><i class="fa-solid fa-clock-rotate-left text-sm" aria-hidden="true"></i></div>@endif
        <div><span class="block text-lg font-semibold tracking-tight text-white">{{ $brand?->brand_name ?: 'WorkLive Pro' }}</span><span class="font-mono text-xs text-slate-500">{{ $brand?->brand_subtitle ?: 'Live Operations' }}</span></div>
    </div>
    <nav class="flex-1 space-y-1.5 p-4">
        <span class="mb-2 block px-3 font-mono text-[10px] font-semibold uppercase tracking-widest text-slate-500">Administrador</span>
        <a href="{{ route('dashboard') }}" class="flex w-full items-center space-x-3 rounded-md px-3 py-2 text-sm font-medium transition-all {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"><i class="fa-solid fa-table-columns w-4 text-center text-sm" aria-hidden="true"></i><span>{{ $menu['dashboard'] }}</span></a>
        <a href="{{ route('employees') }}" class="flex w-full items-center space-x-3 rounded-md px-3 py-2 text-sm font-medium transition-all {{ request()->routeIs('employees*') ? 'bg-indigo-600 text-white shadow' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"><i class="fa-solid fa-users w-4 text-center text-sm" aria-hidden="true"></i><span>{{ $menu['employees'] }}</span></a>

        @php($reportsActive = request()->routeIs('reports*', 'time-clock*'))
        <div class="space-y-1 rounded-lg {{ $reportsActive ? 'bg-slate-800/45 py-1.5' : '' }}">
            <a href="{{ route('reports') }}" class="flex w-full items-center space-x-3 rounded-md px-3 py-2 text-sm font-medium transition-all {{ request()->routeIs('reports*') ? 'bg-indigo-600 text-white shadow' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"><i class="fa-solid fa-chart-column w-4 text-center text-sm" aria-hidden="true"></i><span>{{ $menu['reports'] }}</span></a>
            <a href="{{ route('time-clock') }}" class="ml-5 flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-medium transition {{ request()->routeIs('time-clock*') ? 'bg-indigo-500/25 text-indigo-200' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}"><i class="fa-solid fa-user-clock w-3 text-center" aria-hidden="true"></i><span>{{ $menu['time_clock'] }}</span></a>
        </div>

        <a href="{{ route('policies') }}" class="flex w-full items-center space-x-3 rounded-md px-3 py-2 text-sm font-medium transition-all {{ request()->routeIs('policies*') ? 'bg-indigo-600 text-white shadow' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"><i class="fa-solid fa-shield-halved w-4 text-center text-sm" aria-hidden="true"></i><span>{{ $menu['policies'] }}</span></a>

        @php($settingsActive = request()->routeIs('settings*'))
        <div class="space-y-1 rounded-lg {{ $settingsActive ? 'bg-slate-800/45 py-1.5' : '' }}">
            <a href="{{ route('settings') }}" class="flex w-full items-center space-x-3 rounded-md px-3 py-2 text-sm font-medium transition-all {{ $settingsActive ? 'bg-indigo-600 text-white shadow' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"><i class="fa-solid fa-gear w-4 text-center text-sm" aria-hidden="true"></i><span>{{ $menu['settings'] }}</span></a>
            <a href="{{ route('settings') }}" class="ml-5 flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-medium transition {{ request()->routeIs('settings') ? 'bg-indigo-500/25 text-indigo-100' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}"><i class="fa-solid fa-sliders w-3 text-center" aria-hidden="true"></i><span>{{ $menu['system'] }}</span></a>
            <a href="{{ route('settings.admins.index') }}" class="ml-5 flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-medium transition {{ request()->routeIs('settings.admins.*') ? 'bg-indigo-500/25 text-indigo-100' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}"><i class="fa-solid fa-user-shield w-3 text-center" aria-hidden="true"></i><span>{{ $menu['administrators'] }}</span></a>
            <a href="{{ route('settings.personalization') }}" class="ml-5 flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-medium transition {{ request()->routeIs('settings.personalization*') ? 'bg-indigo-500/25 text-indigo-100' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}"><i class="fa-solid fa-palette w-3 text-center" aria-hidden="true"></i><span>{{ $menu['personalization'] }}</span></a>
        </div>
    </nav>
    <div class="mx-3 mb-4 flex items-center justify-between rounded-xl border border-slate-800/80 bg-slate-800/40 p-4"><div class="flex items-center space-x-2"><span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span></span><span class="text-xs font-medium text-slate-400">MySQL Sync</span></div><span class="font-mono text-[10px] text-slate-500">Real-time</span></div>
    <div class="mt-auto border-t border-slate-800 bg-slate-950/40 p-4"><div class="mb-3.5 flex items-center space-x-2.5"><div class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-600 bg-slate-700 text-xs font-bold">{{ mb_strtoupper(mb_substr(session('worklive_admin.email','A'),0,1)) }}</div><div class="min-w-0 flex-1"><p class="truncate text-xs font-semibold text-slate-200">Admin WorkLive</p><p class="truncate font-mono text-[10px] text-slate-400">{{ session('worklive_admin.email') }}</p></div></div><form method="post" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center justify-center space-x-1.5 rounded-lg border border-slate-800 px-3 py-2 text-xs font-medium text-slate-400 transition-all hover:border-red-900/40 hover:bg-red-950/20 hover:text-red-400" type="submit"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span>Cerrar sesión</span></button></form></div>
</aside>
