@php
    $maxAppSeconds = max(1, (int) $topApps->max('seconds'));
    $maxDomainSeconds = max(1, (int) $topDomains->max('seconds'));
    $maxIdleToday = max(1, (int) $idleTodayLeaders->max('seconds'));
    $maxIdleWeek = max(1, (int) $idleWeekLeaders->max('seconds'));
    $rankMeta = function (string $name, bool $domain = false): array {
        $value = strtolower($name);
        if ($domain) return match (true) {
            str_contains($value, 'github') => ['fa-brands fa-github', 'github'],
            str_contains($value, 'facebook') => ['fa-brands fa-facebook', 'facebook'],
            str_contains($value, 'google') || str_contains($value, 'gmail') => ['fa-brands fa-google', 'google'],
            str_contains($value, 'hubspot') => ['fa-brands fa-hubspot', 'hubspot'],
            str_contains($value, 'chatgpt') || str_contains($value, 'openai') => ['fa-solid fa-wand-magic-sparkles', 'openai'],
            str_contains($value, 'node.js') || str_contains($value, 'nodejs') => ['fa-brands fa-node-js', 'node'],
            default => ['fa-solid fa-globe', 'domain'],
        };
        return match (true) {
            str_contains($value, 'chatgpt') || str_contains($value, 'openai') => ['fa-solid fa-wand-magic-sparkles', 'openai'],
            str_contains($value, 'brave') => ['fa-solid fa-shield-halved', 'brave'],
            str_contains($value, 'opera') => ['fa-brands fa-opera', 'opera'],
            str_contains($value, 'slack') => ['fa-brands fa-slack', 'slack'],
            str_contains($value, 'spotify') => ['fa-brands fa-spotify', 'spotify'],
            str_contains($value, 'whatsapp') => ['fa-brands fa-whatsapp', 'whatsapp'],
            str_contains($value, 'tracker') => ['fa-solid fa-chart-line', 'tracker'],
            str_contains($value, 'terminal') => ['fa-solid fa-terminal', 'terminal'],
            str_contains($value, 'explorer') => ['fa-solid fa-folder-tree', 'explorer'],
            default => ['fa-solid fa-cube', 'default'],
        };
    };
@endphp
<section class="dashboard-rankings-grid mb-8 grid grid-cols-1 gap-6 xl:grid-cols-2">
    @foreach([['title'=>'Aplicaciones más usadas','subtitle'=>'Tiempo activo registrado esta semana','items'=>$topApps,'max'=>$maxAppSeconds,'icon'=>'fa-solid fa-window-maximize','domain'=>false,'tone'=>'indigo'],['title'=>'Dominios más visitados','subtitle'=>'Navegación web detectada esta semana','items'=>$topDomains,'max'=>$maxDomainSeconds,'icon'=>'fa-solid fa-globe','domain'=>true,'tone'=>'cyan']] as $panel)
    <section class="dashboard-ranking-card rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
        <header class="mb-5 flex items-center justify-between gap-3"><div class="flex items-center gap-3"><span class="dashboard-ranking-icon {{ $panel['tone'] }}"><i class="{{ $panel['icon'] }}"></i></span><div><h3>{{ $panel['title'] }}</h3><p>{{ $panel['subtitle'] }}</p></div></div><span class="dashboard-ranking-count">{{ $panel['items']->count() }} registros</span></header>
        <div class="space-y-3">
            @forelse($panel['items'] as $item)
            @php [$rankIcon, $rankTheme] = $rankMeta((string) $item->label, $panel['domain']); @endphp
            <div class="dashboard-ranking-row"><span class="dashboard-ranking-item-icon dashboard-rank-{{ $rankTheme }}"><i class="{{ $rankIcon }}"></i></span><div class="min-w-0 flex-1"><div class="flex items-center justify-between gap-3"><strong title="{{ $item->label }}">{{ $item->label }}</strong><b>{{ $formatSeconds((int) $item->seconds) }}</b></div><div class="dashboard-ranking-bar dashboard-rank-{{ $rankTheme }}"><i style="width:{{ max(4, min(100, ((int) $item->seconds / $panel['max']) * 100)) }}%"></i></div></div></div>
            @empty
            <div class="dashboard-ranking-empty"><i class="fa-regular fa-folder-open"></i><span>Sin datos registrados hoy.</span></div>
            @endforelse
        </div>
    </section>
    @endforeach
    @foreach([['title'=>'Mayor inactividad hoy','subtitle'=>'Colaboradores con más tiempo IDLE','items'=>$idleTodayLeaders,'max'=>$maxIdleToday,'icon'=>'fa-solid fa-calendar-day','tone'=>'amber'],['title'=>'Mayor inactividad semanal','subtitle'=>'Acumulado desde el lunes','items'=>$idleWeekLeaders,'max'=>$maxIdleWeek,'icon'=>'fa-solid fa-calendar-week','tone'=>'rose']] as $panel)
    <section class="dashboard-ranking-card rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
        <header class="mb-5 flex items-center justify-between gap-3"><div class="flex items-center gap-3"><span class="dashboard-ranking-icon {{ $panel['tone'] }}"><i class="{{ $panel['icon'] }}"></i></span><div><h3>{{ $panel['title'] }}</h3><p>{{ $panel['subtitle'] }}</p></div></div><span class="dashboard-ranking-count">{{ $panel['items']->count() }} personas</span></header>
        <div class="space-y-3">
            @forelse($panel['items'] as $item)
            <div class="dashboard-ranking-row"><span class="dashboard-ranking-avatar">{{ collect(explode(' ', $item->employee_name))->filter()->map(fn($word)=>mb_substr($word,0,1))->take(2)->join('') }}</span><div class="min-w-0 flex-1"><div class="flex items-center justify-between gap-3"><div class="min-w-0"><strong title="{{ $item->employee_name }}">{{ $item->employee_name }}</strong><small>{{ $item->department }}</small></div><b class="text-amber-600">{{ $formatSeconds((int) $item->seconds) }}</b></div><div class="dashboard-ranking-bar amber"><i style="width:{{ max(4, min(100, ((int) $item->seconds / $panel['max']) * 100)) }}%"></i></div></div></div>
            @empty
            <div class="dashboard-ranking-empty"><i class="fa-regular fa-clock"></i><span>Sin inactividad registrada en este periodo.</span></div>
            @endforelse
        </div>
    </section>
    @endforeach
</section>
