@php
    $corporateNow = now($corporateTimezone ?? 'America/Mexico_City');
    $serverNow = now('UTC');
    $brand = $workliveBrand ?? null;
    $menuTitle = fn ($field, $fallback) => $brand?->{$field} ?: $fallback;
    $routeName = request()->route()?->getName();
    $navigationTitle = match (true) {
        $routeName === 'dashboard' => $menuTitle('menu_dashboard', 'Tablero Principal'),
        str_starts_with((string) $routeName, 'employees') => $menuTitle('menu_employees', 'Lista de Empleados'),
        str_starts_with((string) $routeName, 'reports') => $menuTitle('menu_reports', 'Reportes y Exportación'),
        str_starts_with((string) $routeName, 'policies') => $menuTitle('menu_policies', 'Políticas de Uso'),
        str_starts_with((string) $routeName, 'time-clock') => $menuTitle('menu_time_clock', 'RH · Reloj Checador'),
        str_starts_with((string) $routeName, 'settings.admins') => $menuTitle('menu_administrators', 'Administradores'),
        str_starts_with((string) $routeName, 'settings.personalization') => $menuTitle('menu_personalization', 'Personalización'),
        $routeName === 'settings' => $menuTitle('menu_system', 'Sistema'),
        default => $title ?? 'WorkLivePro',
    };
@endphp
<header class="worklive-topbar h-16 shrink-0 border-b border-slate-200 bg-white px-8 shadow-sm">
    <div class="flex h-full items-center justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3"><button id="open-navigation" class="worklive-menu-toggle" type="button" aria-label="Abrir menú" aria-controls="app-sidebar" aria-expanded="false"><i class="fa-solid fa-bars"></i></button><div class="min-w-0"><h1 class="truncate text-xl font-bold tracking-tight text-slate-800">{{ $navigationTitle }}</h1><span class="worklive-mobile-kicker">Panel de administración</span></div></div>
        <div class="flex items-center gap-3 sm:gap-4"><div class="hidden lg:flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 font-mono text-xs font-medium text-slate-500" title="Zona horaria corporativa: {{ $corporateTimezone }}"><i class="fa-regular fa-clock"></i><span>{{ $corporateNow->locale('es')->isoFormat('D MMM YYYY') }}</span><span class="text-slate-300">|</span><span id="live-clock" class="font-bold text-slate-700">{{ $corporateNow->format('H:i:s') }}</span></div><div class="hidden xl:flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 font-mono text-[10px] font-semibold text-amber-800" title="Referencia técnica del reloj del servidor"><i class="fa-solid fa-server"></i><span>Servidor UTC</span><span id="server-clock" class="font-bold">{{ $serverNow->format('H:i:s') }}</span></div><span class="worklive-live-status flex items-center gap-1.5 rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wider text-emerald-600"><span class="h-2 w-2 rounded-full bg-emerald-500"></span><span class="worklive-live-label">Sincronizado Live</span></span></div>
    </div>
</header>
<script>
    const workLiveTimezone = @json($corporateTimezone ?? 'America/Mexico_City');
    const serverUtcAtRender = @json($serverNow->toIso8601String());
    const clientRenderedAt = Date.now();
    const formatTime = (date, timeZone) => new Intl.DateTimeFormat('es-MX', { timeZone, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).format(date);
    const updateLiveClocks = () => { const corporateClock = document.getElementById('live-clock'); if (corporateClock) corporateClock.textContent = formatTime(new Date(), workLiveTimezone); const serverClock = document.getElementById('server-clock'); if (serverClock) serverClock.textContent = formatTime(new Date(new Date(serverUtcAtRender).getTime() + (Date.now() - clientRenderedAt)), 'UTC'); };
    updateLiveClocks(); setInterval(updateLiveClocks, 1000);
    document.addEventListener('DOMContentLoaded', () => { const sidebar = document.getElementById('app-sidebar'), overlay = document.getElementById('navigation-overlay'), open = document.getElementById('open-navigation'), close = document.getElementById('close-navigation'); const setNavigation = (visible) => { sidebar?.classList.toggle('is-open', visible); overlay?.classList.toggle('is-visible', visible); document.body.classList.toggle('navigation-open', visible); open?.setAttribute('aria-expanded', visible ? 'true' : 'false'); }; open?.addEventListener('click', () => setNavigation(true)); close?.addEventListener('click', () => setNavigation(false)); overlay?.addEventListener('click', () => setNavigation(false)); sidebar?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setNavigation(false))); document.addEventListener('keydown', event => { if (event.key === 'Escape') setNavigation(false); }); });
</script>
