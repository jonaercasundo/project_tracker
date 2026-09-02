{{--
    resources/views/components/dashboard/stat-card.blade.php

    Usage:
    <x-dashboard.stat-card label="Total People" :value="$totalPeople" :footer="$companyName" color="blue">
        <svg>...</svg>
    </x-dashboard.stat-card>
--}}
@php
    $colors = [
        'blue'    => ['bg' => 'bg-blue-50',    'text' => 'text-blue-600',    'border' => 'hover:border-blue-300'],
        'indigo'  => ['bg' => 'bg-indigo-50',  'text' => 'text-indigo-600',  'border' => 'hover:border-indigo-300'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'border' => 'hover:border-emerald-300'],
        'rose'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-600',    'border' => 'hover:border-rose-300'],
    ];
    $c = $colors[$color] ?? $colors['blue'];
@endphp

<div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between group {{ $c['border'] }} transition-colors">
    <div>
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">{{ $label }}</span>
        <span class="text-3xl font-extrabold text-slate-900 block mt-2">{{ $value }}</span>
        @if($footer)
            <span class="text-[10px] text-slate-400 mt-1 block">{{ $footer }}</span>
        @endif
    </div>

    <div class="w-12 h-12 {{ $c['bg'] }} rounded-xl flex items-center justify-center {{ $c['text'] }} group-hover:scale-105 transition-transform">
        {{ $slot }}
    </div>
</div>