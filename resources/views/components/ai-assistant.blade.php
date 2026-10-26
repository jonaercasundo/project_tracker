@props(['workspace' => false])

@auth
    @php
        $assistantUser = auth()->user();
        $assistantCompany = $assistantUser?->currentCompany();
        $assistantContext = $assistantCompany ? app(\App\Services\AiModuleRegistry::class)->pageContext($assistantUser, $assistantCompany, request()->route()?->getName()) : null;
        $assistantEnabled = $assistantContext && $assistantContext['modules'] !== [];
    @endphp

    @if ($assistantEnabled && ($workspace || ! request()->routeIs('assistant.workspace')))
        <div
            x-data="aiAssistant({
                workspace: @js($workspace),
                company: @js($assistantCompany->code),
                context: @js($assistantContext),
                routes: {
                    conversations: @js(route('assistant.conversations.index')),
                    create: @js(route('assistant.conversations.store')),
                    show: @js(route('assistant.conversations.show', ['conversation' => '__ID__'])),
                    destroy: @js(route('assistant.conversations.destroy', ['conversation' => '__ID__'])),
                    stream: @js(route('assistant.messages.stream', ['conversation' => '__ID__'])),
                    capabilities: @js(route('assistant.capabilities')),
                    full: @js(route('assistant.workspace'))
                }
            })"
            x-init="init()"
            @keydown.escape.window="if (open) close()"
            class="fixed z-[80] bottom-5 right-5 sm:bottom-6 sm:right-6"
            data-ai-assistant
        >
            @unless ($workspace)
                <button type="button" @click="toggle()" class="flex items-center gap-3 rounded-full bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-xl transition hover:bg-slate-700 focus:outline-none focus:ring-4 focus:ring-blue-300" aria-label="Open Project Tracker Assistant">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-500 text-xs">AI</span>
                    <span class="hidden sm:inline">Ask assistant</span>
                </button>
            @endunless

            <section x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-[81] bg-slate-950/30 sm:bg-transparent" @click.self="if (!workspace) close()" :class="workspace ? 'sm:inset-0' : ''">
                <div class="fixed inset-x-0 bottom-0 flex h-[min(88vh,760px)] flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:inset-auto sm:bottom-6 sm:right-6 sm:h-[min(720px,calc(100vh-3rem))] sm:w-[min(440px,calc(100vw-3rem))] sm:rounded-2xl" :class="workspace ? 'sm:!inset-6 sm:!h-auto sm:!w-auto sm:rounded-2xl' : ''" role="dialog" aria-modal="true" aria-label="Project Tracker Assistant">
                    <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-5 py-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2"><h2 class="truncate text-base font-semibold text-slate-900">Project Tracker Assistant</h2><span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700" x-text="context?.label || company"></span></div>
                            <p class="mt-1 truncate text-xs text-slate-500" x-text="context?.page?.title || 'Context-aware workspace help'"></p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <button x-show="conversationId" type="button" @click="deleteConversation()" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-rose-50 hover:text-rose-700">Delete chat</button>
                            <a x-show="!workspace" :href="routes.full" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">Full screen</a>
                            <button x-show="!workspace" type="button" @click="close()" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Close assistant">×</button>
                        </div>
                    </header>

                    <div class="grid min-h-0 flex-1 grid-cols-1 md:grid-cols-[190px_minmax(0,1fr)]">
                        <aside class="hidden border-r border-slate-200 bg-slate-50 p-3 md:flex md:flex-col">
                            <button type="button" @click="newConversation()" class="mb-3 rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700">New conversation</button>
                            <p class="mb-2 px-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Recent</p>
                            <div class="min-h-0 space-y-1 overflow-y-auto">
                                <template x-for="item in conversations" :key="item.id">
                                    <button type="button" @click="load(item.id)" class="block w-full truncate rounded-lg px-2.5 py-2 text-left text-xs hover:bg-white" :class="conversationId === item.id ? 'bg-white font-semibold text-slate-900 shadow-sm' : 'text-slate-600'" x-text="item.title"></button>
                                </template>
                            </div>
                        </aside>

                        <div class="flex min-h-0 min-w-0 flex-col">
                            <div class="border-b border-slate-100 px-4 py-3 md:hidden">
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="newConversation()" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-medium text-white">New conversation</button>
                                    <select aria-label="Recent conversations" @change="if ($event.target.value) load($event.target.value)" class="min-w-0 flex-1 rounded-lg border-slate-200 py-2 text-xs text-slate-700">
                                        <option value="">Recent conversations</option>
                                        <template x-for="item in conversations" :key="item.id"><option :value="item.id" x-text="item.title"></option></template>
                                    </select>
                                </div>
                            </div>
                            <div x-ref="scrollArea" class="min-h-0 flex-1 space-y-4 overflow-y-auto px-4 py-4">
                                <template x-if="messages.length === 0 && context?.page?.help">
                                    <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-4 text-sm leading-6 text-slate-700">
                                        <p x-text="context.page.help"></p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <template x-for="suggestion in context.suggestions || []" :key="suggestion"><button type="button" @click="send(suggestion)" class="rounded-full border border-blue-200 bg-white px-3 py-1.5 text-xs text-blue-800 hover:bg-blue-100" x-text="suggestion"></button></template>
                                        </div>
                                        <div x-show="context.links?.length" class="mt-3 flex flex-wrap gap-2 border-t border-blue-100 pt-3">
                                            <template x-for="link in context.links || []" :key="link.url"><a :href="link.url" class="text-xs font-medium text-blue-700 underline" x-text="link.label"></a></template>
                                        </div>
                                    </div>
                                </template>
                                <template x-for="(message, index) in messages" :key="index">
                                    <div class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                                        <div class="max-w-[92%] rounded-2xl px-3.5 py-2.5 text-sm leading-6 whitespace-pre-wrap" :class="message.role === 'user' ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-800'" x-text="message.content"></div>
                                    </div>
                                </template>
                                <p x-show="loading" class="text-xs text-slate-500">Assistant is thinking…</p>
                                <div x-show="error" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
                                    <p x-text="error" aria-live="polite"></p>
                                    <button x-show="lastPrompt" type="button" @click="send(lastPrompt)" class="mt-2 text-xs font-semibold underline">Retry</button>
                                </div>
                            </div>
                            <form @submit.prevent="submit()" class="border-t border-slate-200 bg-white p-3">
                                <label class="sr-only" for="assistant-message">Message the assistant</label>
                                <div class="flex items-end gap-2 rounded-xl border border-slate-300 p-2 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100">
                                    <textarea id="assistant-message" x-model="draft" @keydown.enter.prevent="if (!$event.shiftKey) submit()" rows="2" maxlength="4000" :disabled="loading" placeholder="Ask about this page or your workspace…" class="max-h-32 min-h-[48px] flex-1 resize-y border-0 bg-transparent text-sm text-slate-800 placeholder:text-slate-400 focus:ring-0 disabled:opacity-60"></textarea>
                                    <button type="submit" :disabled="loading || !draft.trim()" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">Send</button>
                                </div>
                                <p class="mt-2 px-1 text-[10px] text-slate-400">Messages are sent to OpenAI. Page form values are never attached automatically.</p>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    @endif
@endauth
