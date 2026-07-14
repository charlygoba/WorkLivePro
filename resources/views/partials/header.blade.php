@php
    $corporateNow = now($corporateTimezone ?? 'America/Mexico_City');
    $serverNow = now('UTC');
@endphp
<header class="h-16 shrink-0 border-b border-slate-200 bg-white px-8 flex items-center justify-between shadow-sm">
    <h1 class="text-xl font-bold tracking-tight text-slate-800">{{ $title ?? 'WorkLivePro' }}</h1>
    <div class="flex items-center gap-3 sm:gap-4">
        <div class="hidden lg:flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 font-mono text-xs font-medium text-slate-500" title="Zona horaria corporativa: {{ $corporateTimezone }}">
            <span>◷</span>
            <span>{{ $corporateNow->locale('es')->isoFormat('D MMM YYYY') }}</span>
            <span class="text-slate-300">|</span>
            <span id="live-clock" class="font-bold text-slate-700">{{ $corporateNow->format('H:i:s') }}</span>
        </div>
        <div class="hidden xl:flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 font-mono text-[10px] font-semibold text-amber-800" title="Referencia técnica del reloj del servidor. Los eventos se almacenan en UTC y se muestran con la zona corporativa.">
            <span>◉</span>
            <span>Servidor UTC</span>
            <span id="server-clock" class="font-bold">{{ $serverNow->format('H:i:s') }}</span>
        </div>
        <span class="flex items-center gap-1.5 rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wider text-emerald-600"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Sincronizado Live</span>
    </div>
</header>
<script>
    const workLiveTimezone = @json($corporateTimezone ?? 'America/Mexico_City');
    const serverUtcAtRender = @json($serverNow->toIso8601String());
    const clientRenderedAt = Date.now();
    const formatTime = (date, timeZone) => new Intl.DateTimeFormat('es-MX', { timeZone, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).format(date);
    const updateLiveClocks = () => {
        const corporateClock = document.getElementById('live-clock');
        if (corporateClock) corporateClock.textContent = formatTime(new Date(), workLiveTimezone);
        const serverClock = document.getElementById('server-clock');
        if (serverClock) serverClock.textContent = formatTime(new Date(new Date(serverUtcAtRender).getTime() + (Date.now() - clientRenderedAt)), 'UTC');
    };
    updateLiveClocks();
    setInterval(updateLiveClocks, 1000);
</script>
