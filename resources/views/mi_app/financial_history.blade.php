<section class="my-6 rounded border bg-white p-4 text-sm">
    <h2 class="mb-3 font-semibold">Approval History / Financial activity history</h2>
    <ol class="space-y-3">
        @forelse($activities as $activity)
            <li>
                <strong>{{ str_replace('_', ' ', ucfirst($activity->event)) }}</strong>
                <div>{{ $activity->actor_name_snapshot }} @if($activity->actor_role_snapshot) ({{ $activity->actor_role_snapshot }}) @endif</div>
                <div class="text-gray-600">{{ $activity->created_at->format('M d, Y H:i:s') }}</div>
                @if($activity->previous_status || $activity->new_status)
                    <div class="text-gray-600">{{ $activity->previous_status ?? 'New record' }} → {{ $activity->new_status ?? 'Unchanged' }}</div>
                @endif
                @if($activity->amount !== null)<div>{{ $activity->currency }} {{ $activity->amount }}</div>@endif
                @if($activity->reference_no)<div>Reference: {{ $activity->reference_no }}</div>@endif
                @if($activity->note)<div>{{ $activity->note }}</div>@endif
            </li>
        @empty
            <li class="text-gray-600">No recorded activity history is available for this record.</li>
        @endforelse
    </ol>
</section>
