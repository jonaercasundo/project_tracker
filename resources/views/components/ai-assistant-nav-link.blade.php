@can('ai.assistant.use')
    <a href="{{ route('assistant.workspace') }}"
       @class([
           'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-violet-400',
           'bg-violet-50 text-violet-800' => request()->routeIs('assistant.workspace'),
           'text-slate-600 hover:bg-violet-50 hover:text-violet-800' => ! request()->routeIs('assistant.workspace'),
       ])>
        <svg class="h-4 w-4 shrink-0 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.7 5.3L19 10l-5.3 1.7L12 17l-1.7-5.3L5 10l5.3-1.7L12 3zm7 12 .8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15zM5 16l.7 1.8 1.8.7-1.8.7L5 21l-.7-1.8-1.8-.7 1.8-.7L5 16z" />
        </svg>
        <span>AI Assistant</span>
    </a>
@endcan
