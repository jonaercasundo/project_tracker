@props(['workspace' => false])

@auth
    @php
        $assistantUser = auth()->user();
        $assistantCompany = $assistantUser?->currentCompany();
        $assistantContext = $assistantCompany ? app(\App\Services\AiModuleRegistry::class)->pageContext($assistantUser, $assistantCompany, request()->route()?->getName()) : null;
        $assistantAuthorized = $assistantUser?->can('ai.assistant.use') ?? false;
        $assistantConfiguration = app(\App\Services\AiProviderConfiguration::class);
        $assistantStatus = $assistantCompany === null
            ? 'select_company'
            : (! $assistantConfiguration->isEnabled()
                ? 'disabled'
                : ($assistantConfiguration->apiKey() === null ? 'not_configured' : 'ready'));
    @endphp

    @if ($assistantAuthorized && ($workspace || ! request()->routeIs('assistant.workspace')))
        <div
            x-data="aiAssistant({
                workspace: @js($workspace),
                company: @js($assistantCompany?->code),
                status: @js($assistantStatus),
                context: @js($assistantContext),
                routes: {
                    conversations: @js(route('assistant.conversations.index')),
                    create: @js(route('assistant.conversations.store')),
                    show: @js(route('assistant.conversations.show', ['conversation' => '__ID__'])),
                    destroy: @js(route('assistant.conversations.destroy', ['conversation' => '__ID__'])),
                    stream: @js(route('assistant.messages.stream', ['conversation' => '__ID__'])),
                    capabilities: @js(route('assistant.capabilities')),
                    quoteShow: @js(route('assistant.quotations.show', ['conversation' => '__ID__'])),
                    quoteStart: @js(route('assistant.quotations.start', ['conversation' => '__ID__'])),
                    quotePreview: @js(route('assistant.quotations.preview', ['conversation' => '__ID__'])),
                    quoteConfirm: @js(route('assistant.quotations.confirm', ['conversation' => '__ID__'])),
                    quoteProducts: @js(route('assistant.quotations.products')),
                    full: @js(route('assistant.workspace'))
                }
            })"
            x-init="init()"
            @keydown.escape.window="if (open) close()"
            class="fixed z-[110] bottom-5 right-5 sm:bottom-6 sm:right-6"
            data-ai-assistant
        >
            @unless ($workspace)
                <button type="button" @click="toggle()" class="flex items-center gap-3 rounded-full bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-xl transition hover:bg-slate-700 focus:outline-none focus:ring-4 focus:ring-blue-300" aria-label="Open AI Assistant">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-500 text-xs">AI</span>
                    <span>AI Assistant</span>
                </button>
            @endunless

            <section x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-[111] bg-slate-950/30 sm:bg-transparent" @click.self="if (!workspace) close()" :class="workspace ? 'sm:inset-0' : ''">
                <div class="fixed inset-x-0 bottom-0 flex h-[min(88vh,760px)] flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:inset-auto sm:bottom-6 sm:right-6 sm:h-[min(720px,calc(100vh-3rem))] sm:w-[min(440px,calc(100vw-3rem))] sm:rounded-2xl" :class="workspace ? 'sm:!inset-6 sm:!h-auto sm:!w-auto sm:rounded-2xl' : ''" role="dialog" aria-modal="true" aria-label="Project Tracker Assistant">
                    <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-5 py-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2"><h2 class="truncate text-base font-semibold text-slate-900">AI Assistant</h2><span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700" x-text="context?.label || company || 'Workspace'"></span></div>
                            <p class="mt-1 truncate text-xs text-slate-500" x-text="context?.page?.title || 'Context-aware workspace help'"></p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <button x-show="conversationId" type="button" @click="deleteConversation()" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-rose-50 hover:text-rose-700">Delete chat</button>
                            <a x-show="!workspace" :href="routes.full" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">Full screen</a>
                            <button x-show="!workspace" type="button" @click="close()" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Close assistant">×</button>
                        </div>
                    </header>

                    <div x-show="status !== 'ready'" role="status" aria-live="polite" class="border-b border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-900" x-text="statusMessage()"></div>

                    <div class="grid min-h-0 flex-1 grid-cols-1 md:grid-cols-[190px_minmax(0,1fr)]">
                        <aside class="hidden border-r border-slate-200 bg-slate-50 p-3 md:flex md:flex-col">
                            <button type="button" @click="newConversation()" :disabled="status !== 'ready'" class="mb-3 rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50">New conversation</button>
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
                                    <button type="button" @click="newConversation()" :disabled="status !== 'ready'" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-medium text-white disabled:cursor-not-allowed disabled:opacity-50">New conversation</button>
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
                                        <div class="max-w-[92%] min-w-0 rounded-2xl px-3.5 py-2.5 text-sm leading-6 whitespace-pre-wrap" :class="message.role === 'user' ? 'bg-blue-50 text-slate-900' : 'border border-slate-200 bg-white text-slate-800'">
                                            <p x-text="message.content"></p>
                                            <template x-for="product in message.products || message.metadata?.products || []" :key="product.id">
                                                <article x-show="!(message.dismissedProducts || []).includes(product.id)" class="mt-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                                    <div class="flex gap-3">
                                                        <img x-show="product.image" :src="product.image" :alt="product.name" class="h-16 w-16 shrink-0 rounded-lg border border-slate-100 object-cover">
                                                        <div x-show="!product.image" class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-400">MI</div>
                                                        <div class="min-w-0">
                                                            <h3 class="font-semibold text-slate-900" x-text="product.name"></h3>
                                                            <p class="text-xs text-slate-500" x-text="`SKU: ${product.sku || product.code || '—'}`"></p>
                                                            <p class="mt-1 text-xs text-slate-700" x-text="product.price === null ? 'Selling price unavailable' : `Selling price: ₱${Number(product.price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`"></p>
                                                            <p class="text-[11px] text-slate-500" x-text="product.price === null ? 'Ask an authorized pricing administrator' : 'MI product selling price · verify before quotation'"></p>
                                                        </div>
                                                    </div>
                                                    <div class="mt-3 flex flex-wrap gap-2">
                                                        <button x-show="product.price !== null" type="button" @click="startQuote(product)" class="rounded-full bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white">Yes, create quotation</button>
                                                        <button type="button" @click="send(`Compare ${product.name} with similar products`)" class="rounded-full border border-slate-300 px-3 py-1.5 text-xs text-slate-700">Compare products</button>
                                                        <a :href="`/mi/${product.id}`" class="rounded-full border border-slate-300 px-3 py-1.5 text-xs text-slate-700">View product details</a>
                                                        <button type="button" @click="message.dismissedProducts = [...(message.dismissedProducts || []), product.id]" class="rounded-full border border-slate-300 px-3 py-1.5 text-xs text-slate-500">No, thanks</button>
                                                    </div>
                                                </article>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <section x-show="quote" class="rounded-xl border border-blue-200 bg-white p-4 shadow-sm" aria-label="Quotation draft workflow">
                                    <div class="flex items-center justify-between"><h3 class="font-semibold text-slate-900">Quotation draft</h3><button x-show="!quote?.quotation" type="button" @click="cancelQuote()" class="text-xs text-slate-500 underline">Cancel</button></div>
                                    <p class="mt-1 text-xs text-slate-500">Prices use current MI selling price records. No tax or discount rules are configured here.</p>
                                    <div x-show="!quote?.quotation">
                                        <label class="mt-3 block text-xs font-medium text-slate-700">Customer / company</label>
                                        <input x-model="quoteDraft.customer_name" maxlength="150" class="mt-1 w-full rounded-lg border-slate-300 text-sm" placeholder="Customer name">
                                        <label class="mt-3 block text-xs font-medium text-slate-700">Add product by name or SKU</label>
                                        <input x-model="quoteSearch" @input.debounce.300ms="searchQuoteProducts()" @keydown.escape="quoteProducts = []" class="mt-1 w-full rounded-lg border-slate-300 text-sm" placeholder="Search MI products">
                                        <p x-show="quoteSearching" class="mt-1 text-xs text-slate-500" role="status">Searching products…</p>
                                        <div x-show="quoteProducts.length" class="mt-1 max-h-32 overflow-y-auto rounded-lg border border-slate-200 bg-white">
                                            <template x-for="product in quoteProducts" :key="product.id"><button type="button" @click="addQuoteProduct(product)" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50"><span x-text="product.name"></span><span class="ml-2 text-xs text-slate-500" x-text="product.sku"></span></button></template>
                                        </div>
                                        <div class="mt-3 space-y-2">
                                            <template x-for="(item, index) in quoteDraft.items" :key="item.id"><div class="flex items-center gap-2 rounded-lg bg-slate-50 p-2"><span class="min-w-0 flex-1 truncate text-xs text-slate-700" x-text="item.name || `Product #${item.id}`"></span><label class="sr-only" :for="`quote-quantity-${index}`">Quantity</label><input :id="`quote-quantity-${index}`" type="number" min="1" max="100000" step="1" x-model.number="item.quantity" @input="quotePreview = null" class="w-20 rounded border-slate-300 py-1 text-xs"><button type="button" @click="removeQuoteProduct(index)" class="px-1 text-xs text-rose-600">Remove</button></div></template>
                                        </div>
                                        <button type="button" @click="previewQuote()" :disabled="quoteLoading || !quoteDraft.items.length || !quoteDraft.customer_name.trim()" class="mt-3 rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50" x-text="quoteLoading ? 'Loading…' : 'Preview quotation'"></button>
                                    </div>
                                    <div x-show="quotePreview && !quote?.quotation" class="mt-3 rounded-lg border border-slate-200 p-3">
                                        <p class="font-medium text-slate-800" x-text="quotePreview?.customer_name"></p>
                                        <template x-for="item in quotePreview?.items || []" :key="item.id"><div class="flex justify-between gap-2 py-1 text-xs text-slate-600"><span x-text="`${item.name} · ${item.quantity} × ₱${Number(item.unit_price).toLocaleString(undefined, { minimumFractionDigits: 2 })}`"></span><span x-text="`₱${Number(item.subtotal).toLocaleString(undefined, { minimumFractionDigits: 2 })}`"></span></div></template>
                                        <div class="mt-2 flex justify-between border-t border-slate-200 pt-2 text-sm font-semibold"><span>Total (PHP)</span><span x-text="`₱${Number(quotePreview?.total || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}`"></span></div>
                                        <button type="button" @click="confirmQuote()" :disabled="quoteLoading" class="mt-3 rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white">Confirm and save draft</button>
                                    </div>
                                    <div x-show="quote?.quotation" class="mt-3 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900"><p x-text="`Saved as ${quote?.quotation?.quote_number || 'quotation draft'}`"></p><a :href="quote?.quotation?.print_url" target="_blank" class="mt-2 inline-block font-semibold underline">Open printable quotation</a></div>
                                    <p x-show="quoteError" x-text="quoteError" role="alert" class="mt-2 text-xs text-rose-700"></p>
                                </section>
                                <p x-show="loading" class="text-xs text-slate-500">Assistant is thinking…</p>
                                <div x-show="error" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
                                    <p x-text="error" aria-live="polite"></p>
                                    <button x-show="lastPrompt" type="button" @click="send(lastPrompt)" class="mt-2 text-xs font-semibold underline">Retry</button>
                                </div>
                            </div>
                            <form @submit.prevent="submit()" class="border-t border-slate-200 bg-white p-3">
                                <label class="sr-only" for="assistant-message">Message the assistant</label>
                                <div class="flex items-end gap-2 rounded-xl border border-slate-300 p-2 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100">
                                    <textarea id="assistant-message" x-model="draft" @keydown.enter.prevent="if (!$event.shiftKey) submit()" rows="2" maxlength="4000" :disabled="loading || status !== 'ready'" :placeholder="status === 'ready' ? 'Ask about this page or your workspace…' : statusMessage()" class="max-h-32 min-h-[48px] flex-1 resize-y border-0 bg-transparent text-sm text-slate-800 placeholder:text-slate-400 focus:ring-0 disabled:opacity-60"></textarea>
                                    <button type="submit" :disabled="loading || status !== 'ready' || !draft.trim()" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">Send</button>
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
