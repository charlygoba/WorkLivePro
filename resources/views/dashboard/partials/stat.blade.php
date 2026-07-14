<div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-start justify-between">
    <div class="space-y-2">
        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider block font-mono">{{ $label }}</span>
        <div class="flex items-baseline space-x-2"><span class="text-3xl font-extrabold text-slate-950 font-mono">{{ $value }}</span><span class="text-xs text-slate-400 font-medium font-mono">{{ $suffix }}</span></div>
        <span class="text-[10px] text-{{ $tone }}-600 font-medium flex items-center space-x-1"><span class="h-1.5 w-1.5 rounded-full bg-{{ $tone }}-500 block {{ $tone === 'emerald' ? 'animate-pulse' : '' }}"></span><span>{{ $note }}</span></span>
    </div>
    <div class="bg-{{ $tone }}-50 p-2.5 rounded-xl border border-{{ $tone }}-100">
        @if($icon === 'laptop')<svg class="w-5 h-5 text-{{ $tone }}-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M2 20h20"/></svg>
        @elseif($icon === 'activity')<svg class="w-5 h-5 text-{{ $tone }}-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h4l3-8 4 16 3-8h4"/></svg>
        @elseif($icon === 'lock')<svg class="w-5 h-5 text-{{ $tone }}-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
        @else<svg class="w-5 h-5 text-{{ $tone }}-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg>@endif
    </div>
</div>
