@php
    $bidding = $evidenceData['bidding'];
    $noaFound = $evidenceData['scopes']->contains(fn (array $scope) => $scope['events']->contains(fn (array $entry) => $entry['event']->event_type === 'noa_verified'));
    $ntpFound = $evidenceData['scopes']->contains(fn (array $scope) => $scope['events']->contains(fn (array $entry) => $entry['event']->event_type === 'ntp_verified'));
    $biddingRoutePrefix = auth()->user()->hasRole('finance') ? 'bidding' : 'project.bidding';
    $documentRoutePrefix = $biddingRoutePrefix.'.documents';
@endphp

<section class="rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm" aria-labelledby="bidding-evidence-heading">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wide text-indigo-600">Operations evidence</p>
            <h2 id="bidding-evidence-heading" class="mt-1 text-lg font-bold text-slate-900">Bidding Documents &amp; Award References</h2>
            @if($bidding)
                <p class="mt-1 text-sm text-slate-600">{{ $bidding->project_name }} <span class="text-slate-400">·</span> Bidding record #{{ $bidding->getKey() }} <span class="text-slate-400">·</span> Status: {{ $bidding->status }}</p>
            @else
                <p class="mt-1 text-sm text-slate-600">No verified Bidding record is linked to this project.</p>
            @endif
        </div>
        @if($bidding && ! auth()->user()->hasRole('finance'))
            <a href="{{ route($biddingRoutePrefix.'.show', $bidding) }}" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Open Bidding record</a>
        @endif
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <p class="text-xs font-bold text-slate-700">Awarded — verified NOA</p>
            <p class="mt-1 text-xs {{ $noaFound ? 'text-emerald-700' : 'text-slate-500' }}">{{ $noaFound ? 'Verified NOA evidence recorded' : 'No verified NOA recorded' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <p class="text-xs font-bold text-slate-700">Implementation — verified NTP</p>
            <p class="mt-1 text-xs {{ $ntpFound ? 'text-emerald-700' : 'text-slate-500' }}">{{ $ntpFound ? 'Verified NTP evidence recorded' : 'No verified NTP recorded' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <p class="text-xs font-bold text-slate-700">Delivered — verified acceptance</p>
            <p class="mt-1 text-xs {{ $evidenceData['deliveryVerified'] ? 'text-emerald-700' : 'text-slate-500' }}">{{ $evidenceData['deliveryVerified'] ? 'Verified delivery acceptance recorded' : 'No verified delivery acceptance linked' }}</p>
        </div>
    </div>

    <div class="mt-5 space-y-3">
        <h3 class="text-sm font-bold text-slate-800">Project and lot evidence</h3>
        @foreach($evidenceData['scopes'] as $scope)
            <div class="rounded-xl border border-slate-200 p-4">
                <h4 class="text-sm font-semibold text-slate-800">{{ $scope['lot'] }}</h4>
                @if($scope['events']->isEmpty())
                    <p class="mt-2 text-xs text-slate-500">No verified NOA, NTP, or delivery acceptance evidence recorded for this scope.</p>
                @else
                    <div class="mt-3 space-y-3">
                        @foreach($scope['events'] as $entry)
                            @php
                                $event = $entry['event'];
                                $metadata = $event->metadata ?? [];
                                $eventLabel = match ($event->event_type) {
                                    'noa_verified' => 'Notice of Award',
                                    'ntp_verified' => 'Notice to Proceed',
                                    default => 'Verified Delivery Acceptance',
                                };
                            @endphp
                            <div class="border-t border-slate-100 pt-3 first:border-t-0 first:pt-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800">{{ $eventLabel }} · Verified</span>
                                    <span class="text-xs text-slate-500">{{ $metadata['verified_at'] ?? $event->created_at?->format('M d, Y g:i A') ?? 'Verification date unavailable' }}</span>
                                </div>
                                <dl class="mt-2 grid gap-x-6 gap-y-1 text-xs sm:grid-cols-2">
                                    @if($event->event_type === 'noa_verified')
                                        <div><dt class="inline text-slate-500">NOA reference: </dt><dd class="inline font-medium text-slate-800">{{ $metadata['reference'] ?? 'Not recorded' }}</dd></div>
                                        <div><dt class="inline text-slate-500">NOA date: </dt><dd class="inline font-medium text-slate-800">{{ $metadata['event_date'] ?? 'Not recorded' }}</dd></div>
                                        <div><dt class="inline text-slate-500">Award amount: </dt><dd class="inline font-medium text-slate-800">{{ isset($entry['amount']) ? '₱'.number_format((float) $entry['amount'], 2).' ('.$entry['amountSource'].')' : 'Not recorded' }}</dd></div>
                                    @elseif($event->event_type === 'ntp_verified')
                                        <div><dt class="inline text-slate-500">NTP reference: </dt><dd class="inline font-medium text-slate-800">{{ $metadata['reference'] ?? 'Not recorded' }}</dd></div>
                                        <div><dt class="inline text-slate-500">NTP issue date: </dt><dd class="inline font-medium text-slate-800">{{ $metadata['event_date'] ?? 'Not recorded' }}</dd></div>
                                        <div><dt class="inline text-slate-500">Effectivity date: </dt><dd class="inline font-medium text-slate-800">{{ $metadata['effectivity_date'] ?? 'Not recorded' }}</dd></div>
                                    @endif
                                    <div><dt class="inline text-slate-500">Verified by: </dt><dd class="inline font-medium text-slate-800">{{ $event->actor?->name ?? (isset($metadata['verified_by']) ? 'User #'.$metadata['verified_by'] : 'Not recorded') }}</dd></div>
                                    <div><dt class="inline text-slate-500">Status transition: </dt><dd class="inline font-medium text-slate-800">{{ $event->from_stage ?? 'Not recorded' }} → {{ $event->to_stage ?? 'Not recorded' }}</dd></div>
                                </dl>
                                @if($entry['document'])
                                    <div class="mt-2 flex flex-wrap items-center gap-3 text-xs">
                                        <span class="text-slate-600">{{ $entry['document']->display_name }} · v{{ $entry['documentVersion']->version ?? $entry['document']->version }}{{ $entry['documentVersion'] ? ' (verified version)' : '' }}</span>
                                        @if(! $entry['documentVersion'] && $entry['document']->can_preview)
                                            <a class="font-semibold text-indigo-700 hover:underline" href="{{ route($documentRoutePrefix.'.preview', [$bidding, $entry['document']]) }}">View</a>
                                        @endif
                                        @if($entry['documentVersion'])
                                            <a class="font-semibold text-indigo-700 hover:underline" href="{{ route($documentRoutePrefix.'.versions.download', [$bidding, $entry['document'], $entry['documentVersion']]) }}">Download</a>
                                        @else
                                            <a class="font-semibold text-indigo-700 hover:underline" href="{{ route($documentRoutePrefix.'.download', [$bidding, $entry['document']]) }}">Download</a>
                                        @endif
                                    </div>
                                @else
                                    <p class="mt-2 text-xs text-amber-700">No verified document linked.</p>
                                @endif
                                @if($event->note)
                                    <p class="mt-2 text-xs text-slate-500">{{ $event->note }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="mt-5 border-t border-slate-100 pt-4">
        <h3 class="text-sm font-bold text-slate-800">Linked supporting Bidding documents</h3>
        @if($evidenceData['documents']->isEmpty())
            <p class="mt-2 text-xs text-slate-500">{{ $bidding ? 'Not yet uploaded.' : 'No verified document linked.' }}</p>
        @else
            <ul class="mt-2 divide-y divide-slate-100">
                @foreach($evidenceData['documents'] as $document)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2 text-xs">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-800" title="{{ $document->display_name }}">{{ $document->display_name }}</p>
                            <p class="mt-0.5 text-slate-500">{{ $document->description ?: 'Bidding attachment' }} · v{{ $document->version }}@if($document->uploaded_at) · Uploaded {{ $document->uploaded_at->format('M d, Y') }}@endif @if($document->uploader) · Uploaded by {{ $document->uploader->name }}@endif</p>
                        </div>
                        <div class="flex shrink-0 gap-3">
                            @if($document->can_preview)
                                <a class="font-semibold text-indigo-700 hover:underline" href="{{ route($documentRoutePrefix.'.preview', [$bidding, $document]) }}">View</a>
                            @endif
                            <a class="font-semibold text-indigo-700 hover:underline" href="{{ route($documentRoutePrefix.'.download', [$bidding, $document]) }}">Download</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
