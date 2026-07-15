@php
$status = $status ?? 'Draft';
@endphp

@if($status === 'Draft')
    <span class="px-3 py-1 text-xs bg-slate-100 text-slate-700 rounded-full">Draft</span>

@elseif($status === 'For Review')
    <span class="px-3 py-1 text-xs bg-yellow-100 text-yellow-700 rounded-full">For Review</span>

@elseif($status === 'Published')
    <span class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-full">Published</span>

@elseif($status === 'Awarded')
    <span class="px-3 py-1 text-xs bg-green-100 text-green-700 rounded-full">Awarded</span>

@elseif($status === 'Completed')
    <span class="px-3 py-1 text-xs bg-indigo-100 text-indigo-700 rounded-full">Completed</span>

@else
    <span class="px-3 py-1 text-xs bg-red-100 text-red-700 rounded-full">Cancelled</span>
@endif