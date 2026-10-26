@props(['workspace' => false])

@auth
    @php
        $assistantUser = auth()->user();
        $assistantCompany = $assistantUser?->currentCompany();
        $assistantContext = $assistantCompany ? app(\App\Services\AiModuleRegistry::class)->pageContext($assistantUser, $assistantCompany, request()->route()?->getName()) : null;
        $assistantAuthorized = $assistantUser?->can('ai.assistant.use') ?? false;
        $assistantQuoteAllowed = $assistantUser?->can('mi.quotation.create') ?? false;
        $assistantCanManageSettings = $assistantUser?->can('ai.settings.manage') ?? false;
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
                quoteAllowed: @js($assistantQuoteAllowed),
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
                    full: @js(route('assistant.workspace')),
                    dashboard: @js(route('dashboard.launch')),
                    aiSettings: @js($assistantCanManageSettings ? route('admin.ai-configuration.index') : null)
                }
            })"
            x-init="init()"
            @keydown.escape.window="if (contextPanelOpen) contextPanelOpen = false; else if (mobileSidebarOpen) mobileSidebarOpen = false; else if (open && workspace) exitWorkspace(); else if (open) close()"
            class="fixed z-[110] bottom-5 right-5 sm:bottom-6 sm:right-6"
            data-ai-assistant
        >
            @unless ($workspace)
                <button type="button" @click="toggle()" class="flex items-center gap-3 rounded-full bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-xl transition hover:bg-slate-700 focus:outline-none focus:ring-4 focus:ring-blue-300" aria-label="Open AI Assistant">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-500"><x-ai-icon name="sparkles" size="16" /></span>
                    <span>AI Assistant</span>
                </button>
            @endunless

            @if ($workspace)
                <section x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-[111] flex flex-col overflow-hidden bg-[#f5f7fb] text-slate-800" aria-label="AI Assistant workspace">
                    <header class="relative z-20 flex min-h-[76px] items-center justify-between gap-3 border-b border-slate-200/80 bg-white px-4 py-3 shadow-[0_2px_12px_rgba(18,34,61,0.04)] sm:px-6">
                        <div class="flex min-w-0 items-center gap-3">
                            <button type="button" @click="mobileSidebarOpen = true" class="rounded-xl border border-slate-200 p-2 text-slate-600 hover:bg-slate-50 xl:hidden" aria-label="Open conversations"><x-ai-icon name="menu" /></button>
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#203d83] text-white shadow-lg shadow-blue-900/15"><x-ai-icon name="sparkles" size="22" /></div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <h1 class="text-[15px] font-bold tracking-[-0.02em] text-[#14213b] sm:text-base">Project Tracker AI</h1>
                                    <span class="hidden rounded-full border border-blue-100 bg-blue-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-blue-800 sm:inline-flex" x-text="context?.label || company || 'Workspace'"></span>
                                </div>
                                <p class="truncate text-xs text-slate-500" x-text="pageTitle()"></p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="hidden items-center gap-1.5 rounded-full px-2.5 py-1.5 text-[11px] font-semibold sm:inline-flex" :class="status === 'ready' ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800'">
                                <span class="h-1.5 w-1.5 rounded-full" :class="status === 'ready' ? 'bg-emerald-500' : 'bg-amber-500'"></span><span x-text="statusLabel()"></span>
                            </span>
                            <button type="button" @click="contextPanelOpen = !contextPanelOpen" class="rounded-xl border border-slate-200 p-2 text-slate-600 transition hover:bg-slate-50 2xl:hidden" aria-label="Toggle page context"><x-ai-icon name="compass" /></button>
                            <button type="button" @click="newConversation()" :disabled="status !== 'ready'" class="inline-flex items-center gap-2 rounded-xl bg-[#203d83] px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-[#172f6e] disabled:cursor-not-allowed disabled:opacity-50 sm:px-4 sm:text-sm"><x-ai-icon name="plus" size="17" /><span class="hidden sm:inline">New chat</span></button>
                            <button x-show="conversationId" type="button" @click="deleteConversation()" class="rounded-xl p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-700" aria-label="Delete conversation"><x-ai-icon name="trash" size="18" /></button>
                            <button type="button" @click="exitWorkspace()" class="rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close assistant"><x-ai-icon name="close" size="19" /></button>
                        </div>
                    </header>

                    <div class="relative grid min-h-0 flex-1 grid-cols-1 xl:grid-cols-[252px_minmax(0,1fr)] 2xl:grid-cols-[260px_minmax(0,1fr)_284px]">
                        <aside class="hidden min-h-0 flex-col border-r border-[#20324f] bg-[#14233c] text-slate-200 xl:flex">
                            <div class="flex items-center gap-2 px-5 pb-2 pt-6 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400"><x-ai-icon name="message" size="15" /> Your conversations</div>
                            <button type="button" @click="newConversation()" :disabled="status !== 'ready'" class="mx-4 mt-3 flex items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/10 px-3 py-3 text-sm font-semibold text-white transition hover:bg-white/15 disabled:opacity-40"><x-ai-icon name="plus" size="17" /> Start a new chat</button>
                            <label class="mx-4 mt-5 flex items-center gap-2 rounded-xl border border-white/10 bg-white/[0.06] px-3 py-2.5 text-slate-400 focus-within:border-blue-300/60 focus-within:text-white">
                                <x-ai-icon name="search" size="17" /><span class="sr-only">Search conversations</span><input x-model="conversationSearch" type="search" placeholder="Search conversations" class="w-full border-0 bg-transparent p-0 text-xs text-white placeholder:text-slate-500 focus:ring-0">
                            </label>
                            <div class="mt-5 flex items-center justify-between px-5 text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400"><span>Recent chats</span><span class="rounded-full bg-white/10 px-2 py-0.5 text-[10px]" x-text="filteredConversations().length"></span></div>
                            <div class="mt-2 min-h-0 flex-1 space-y-1 overflow-y-auto px-3 pb-4">
                                <template x-if="loadingConversations"><div class="space-y-2 px-2 py-3"><template x-for="item in [1,2,3]" :key="item"><div class="h-10 animate-pulse rounded-lg bg-white/[0.07]"></div></template></div></template>
                                <template x-for="item in filteredConversations()" :key="item.id"><button type="button" @click="load(item.id); mobileSidebarOpen = false" class="group flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-left text-xs transition hover:bg-white/[0.08]" :class="Number(conversationId) === Number(item.id) ? 'bg-white/[0.12] text-white ring-1 ring-white/10' : 'text-slate-300'"><x-ai-icon name="message" size="15" class="shrink-0 text-slate-500 group-hover:text-blue-300" /><span class="min-w-0 flex-1 truncate" x-text="item.title"></span><span class="text-[10px] text-slate-500" x-text="formatTime(item.last_message_at)"></span></button></template>
                                <p x-show="!loadingConversations && filteredConversations().length === 0" class="px-3 py-4 text-xs leading-5 text-slate-500" x-text="conversationSearch ? 'No chats match your search.' : 'Your new conversations will appear here.'"></p>
                            </div>
                            <div class="border-t border-white/10 px-4 py-4"><p class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400"><x-ai-icon name="sparkles" size="13" /> Quick actions</p><template x-for="action in quickActions().slice(0, 2)" :key="`rail-${action.title}`"><button type="button" @click="send(action.prompt)" :disabled="status !== 'ready'" class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-xs text-slate-300 transition hover:bg-white/[0.08] hover:text-white disabled:opacity-40"><x-ai-icon name="search" size="14" x-show="action.icon === 'search'" /><x-ai-icon name="tag" size="14" x-show="action.icon === 'tag'" /><x-ai-icon name="chart" size="14" x-show="action.icon === 'chart'" /><x-ai-icon name="activity" size="14" x-show="action.icon === 'activity'" /><x-ai-icon name="help" size="14" x-show="action.icon === 'help'" /><span class="truncate" x-text="action.title"></span></button></template></div>
                            <div class="border-t border-white/10 px-5 py-4">
                                <div class="flex items-center gap-2 text-xs font-semibold text-white"><x-ai-icon name="shield" size="16" class="text-emerald-300" /> Private workspace</div>
                                <p class="mt-1.5 pl-6 text-[10px] leading-4 text-slate-400">History stays with your account and selected company.</p>
                            </div>
                        </aside>

                        <main class="relative flex min-h-0 min-w-0 flex-col bg-[#f8f9fc]">
                            <div class="flex min-h-[52px] items-center justify-between border-b border-slate-200/70 bg-white/80 px-4 sm:px-7">
                                <div class="flex min-w-0 items-center gap-2 text-xs text-slate-500"><x-ai-icon name="compass" size="16" class="text-[#3c5fd5]" /><span class="truncate" x-text="pageTitle()"></span><span class="text-slate-300">/</span><span class="truncate font-medium text-slate-700" x-text="context?.page?.title || 'Workspace guide'"></span></div>
                                <span class="flex items-center gap-1.5 text-[11px] font-medium" :class="status === 'ready' ? 'text-emerald-700' : 'text-amber-700'"><span class="h-1.5 w-1.5 rounded-full" :class="status === 'ready' ? 'bg-emerald-500' : 'bg-amber-500'"></span><span class="sm:hidden" x-text="statusLabel()"></span><span class="hidden sm:inline" x-text="status === 'ready' ? 'Connected and ready' : statusLabel()"></span></span>
                            </div>

                            <div x-ref="scrollArea" class="min-h-0 flex-1 overflow-y-auto px-4 py-5 sm:px-8 sm:py-8 xl:px-10">
                                <div class="mx-auto flex min-h-full w-full max-w-[900px] flex-col">
                                    <template x-if="status !== 'ready'"><div class="mb-6 flex gap-4 rounded-2xl border p-4 shadow-sm sm:p-5" :class="status === 'disabled' ? 'border-slate-200 bg-white' : 'border-amber-200 bg-[#fffdf7]'">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl" :class="status === 'disabled' ? 'bg-slate-100 text-slate-500' : 'bg-amber-100 text-amber-800'"><x-ai-icon name="sparkles" size="22" /></div>
                                        <div class="min-w-0 flex-1"><h2 class="font-semibold text-slate-900" x-text="statusTitle()"></h2><p class="mt-1 text-sm leading-6 text-slate-600" x-text="statusDetails()"></p><a x-show="status === 'not_configured' && routes.aiSettings" :href="routes.aiSettings" class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-blue-800 underline"><x-ai-icon name="external" size="14" /> Open AI configuration</a></div>
                                    </div></template>

                                    <template x-if="messages.length === 0 && !openingConversation">
                                        <div class="flex flex-1 flex-col justify-center pb-8 pt-4">
                                            <div class="relative mb-7 flex min-h-[190px] items-center justify-center overflow-hidden rounded-[28px] border border-[#e3eaf6] bg-white shadow-[0_16px_44px_rgba(31,55,96,0.06)] sm:min-h-[220px]">
                                                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_50%_20%,rgba(93,128,255,0.13),transparent_60%)]"></div>
                                                <div class="relative flex items-center gap-5 px-5 sm:gap-8 sm:px-10">
                                                    <div class="relative flex h-28 w-28 shrink-0 items-center justify-center sm:h-36 sm:w-36">
                                                        <div class="absolute inset-0 rounded-full border border-blue-100"></div><div class="absolute inset-3 rounded-full border border-dashed border-blue-200"></div><div class="absolute inset-7 rounded-full bg-gradient-to-br from-[#edf2ff] to-[#e5fbf4]"></div><div class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-[#203d83] text-white shadow-xl shadow-blue-900/20 sm:h-[68px] sm:w-[68px]"><x-ai-icon name="compass" size="32" /></div>
                                                        <span class="absolute right-2 top-5 h-3 w-3 rounded-full border-2 border-white bg-[#6c85f4]"></span><span class="absolute bottom-5 left-1 h-2.5 w-2.5 rounded-full border-2 border-white bg-[#4bc9a4]"></span>
                                                    </div>
                                                    <div class="max-w-[520px]">
                                                        <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.18em] text-[#4767cb]">Your context-aware work partner</p>
                                                        <h2 class="max-w-[520px] text-2xl font-bold leading-tight tracking-[-0.04em] text-[#14213b] sm:text-[32px]" x-text="welcomeTitle()"></h2>
                                                        <p class="mt-3 max-w-[500px] text-sm leading-6 text-slate-600" x-text="pageDescription()"></p>
                                                        <p class="mt-3 flex items-center gap-1.5 text-[11px] font-medium text-slate-500"><x-ai-icon name="shield" size="14" class="text-emerald-600" /> Answers use the permissions and records available to you.</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3 flex items-center justify-between gap-3"><div><h3 class="text-sm font-bold text-[#14213b]">A good place to start</h3><p class="mt-0.5 text-xs text-slate-500">Choose a prompt or ask in your own words.</p></div><span class="hidden text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 sm:inline">Suggested for this page</span></div>
                                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                <template x-for="(action, index) in quickActions()" :key="action.title"><button type="button" @click="send(action.prompt)" :disabled="status !== 'ready'" class="group relative flex min-h-[98px] items-start gap-3 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-[0_2px_8px_rgba(25,42,75,0.025)] transition duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-[0_12px_24px_rgba(43,67,126,0.09)] focus:outline-none focus:ring-2 focus:ring-blue-300 disabled:cursor-not-allowed disabled:opacity-50 motion-reduce:transform-none motion-reduce:transition-none">
                                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition" :class="index % 2 ? 'bg-emerald-50 text-emerald-700 group-hover:bg-emerald-100' : 'bg-blue-50 text-blue-800 group-hover:bg-blue-100'"><x-ai-icon name="search" size="19" x-show="action.icon === 'search'" /><x-ai-icon name="tag" size="19" x-show="action.icon === 'tag'" /><x-ai-icon name="document" size="19" x-show="action.icon === 'document'" /><x-ai-icon name="activity" size="19" x-show="action.icon === 'activity'" /><x-ai-icon name="help" size="19" x-show="action.icon === 'help'" /></span><span class="min-w-0 flex-1"><span class="block text-sm font-semibold text-slate-800" x-text="action.title"></span><span class="mt-1 block text-xs leading-5 text-slate-500" x-text="action.subtitle"></span></span><x-ai-icon name="arrow" size="16" class="mt-1 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-blue-600" />
                                                </button></template>
                                            </div>
                                            <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-slate-200/80 pt-4 text-[11px] text-slate-500">
                                                <span class="font-semibold uppercase tracking-[0.12em] text-slate-400">Also available</span>
                                                <template x-for="link in context?.links || []" :key="link.url"><a :href="link.url" class="inline-flex items-center gap-1 text-slate-600 transition hover:text-blue-800"><x-ai-icon name="external" size="13" /><span x-text="link.label"></span></a></template>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="messages.length > 0"><div class="flex flex-1 flex-col gap-6 pb-8">
                                        <template x-for="(message, index) in messages" :key="index">
                                            <article class="flex gap-3 sm:gap-4" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                                                <div x-show="message.role !== 'user'" class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#203d83] text-white shadow-sm"><x-ai-icon name="sparkles" size="16" /></div>
                                                <div class="max-w-[88%] min-w-0 sm:max-w-[78%]">
                                                    <div class="mb-1.5 flex items-center gap-2 px-1" :class="message.role === 'user' ? 'justify-end' : ''"><span class="text-[11px] font-semibold text-slate-700" x-text="message.role === 'user' ? 'You' : 'Project Tracker AI'"></span><time class="text-[10px] text-slate-400" x-text="formatTime(message.created_at)"></time></div>
                                                    <div class="rounded-2xl px-4 py-3.5 text-sm leading-6 shadow-sm" :class="message.role === 'user' ? 'rounded-tr-md bg-[#e9efff] text-[#172b58]' : 'rounded-tl-md border border-slate-200/80 bg-white text-slate-700'">
                                                        <p class="whitespace-pre-wrap" x-text="message.content"></p>
                                                        <template x-for="product in message.products || message.metadata?.products || []" :key="product.id">
                                                            <article x-show="!(message.dismissedProducts || []).includes(product.id)" class="mt-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                                                <div class="flex gap-3"><img x-show="product.image" :src="product.image" :alt="product.name" class="h-16 w-16 shrink-0 rounded-lg border border-slate-100 object-cover"><div x-show="!product.image" class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-400">MI</div><div class="min-w-0"><h3 class="font-semibold text-slate-900" x-text="product.name"></h3><p class="text-xs text-slate-500" x-text="`SKU: ${product.sku || product.code || '—'}`"></p><p class="mt-1 text-xs text-slate-700" x-text="product.price === null ? 'Selling price unavailable' : `Selling price: ₱${Number(product.price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`"></p><p class="text-[11px] text-slate-500" x-text="product.price === null ? 'Ask an authorized pricing administrator' : 'MI product selling price · verify before quotation'"></p></div></div>
                                                                <div class="mt-3 flex flex-wrap gap-2"><button x-show="product.price !== null" type="button" @click="startQuote(product)" class="rounded-full bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white"><x-ai-icon name="document" size="13" class="mr-1 inline" /> Create quotation</button><button type="button" @click="send(`Compare ${product.name} with similar products`)" class="rounded-full border border-slate-300 px-3 py-1.5 text-xs text-slate-700"><x-ai-icon name="chart" size="13" class="mr-1 inline" /> Compare similar</button><a :href="`/mi/${product.id}`" class="rounded-full border border-slate-300 px-3 py-1.5 text-xs text-slate-700"><x-ai-icon name="external" size="13" class="mr-1 inline" /> View product</a><button type="button" @click="message.dismissedProducts = [...(message.dismissedProducts || []), product.id]" class="rounded-full border border-slate-300 px-3 py-1.5 text-xs text-slate-500">No thanks</button></div>
                                                            </article>
                                                        </template>
                                                    </div>
                                                </div>
                                                <div x-show="message.role === 'user'" class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#dbe6ff] text-[11px] font-bold text-[#254386]">ME</div>
                                            </article>
                                        </template>
                                    </div></template>
                                    <section x-show="quote" class="mb-5 rounded-2xl border border-blue-200 bg-white p-4 shadow-sm" aria-label="Quotation draft workflow">
                                        <div class="flex items-center justify-between"><h3 class="font-semibold text-slate-900">Quotation draft</h3><button x-show="!quote?.quotation" type="button" @click="cancelQuote()" class="text-xs text-slate-500 underline">Cancel</button></div>
                                        <p class="mt-1 text-xs text-slate-500">Prices use current MI selling price records. No tax or discount rules are configured here.</p>
                                        <div x-show="!quote?.quotation"><label class="mt-3 block text-xs font-medium text-slate-700">Customer / company</label><input x-model="quoteDraft.customer_name" maxlength="150" class="mt-1 w-full rounded-lg border-slate-300 text-sm" placeholder="Customer name"><label class="mt-3 block text-xs font-medium text-slate-700">Add product by name or SKU</label><input x-model="quoteSearch" @input.debounce.300ms="searchQuoteProducts()" @keydown.escape="quoteProducts = []" class="mt-1 w-full rounded-lg border-slate-300 text-sm" placeholder="Search MI products"><p x-show="quoteSearching" class="mt-1 text-xs text-slate-500" role="status">Searching products…</p><div x-show="quoteProducts.length" class="mt-1 max-h-32 overflow-y-auto rounded-lg border border-slate-200 bg-white"><template x-for="product in quoteProducts" :key="product.id"><button type="button" @click="addQuoteProduct(product)" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50"><span x-text="product.name"></span><span class="ml-2 text-xs text-slate-500" x-text="product.sku"></span></button></template></div><div class="mt-3 space-y-2"><template x-for="(item, index) in quoteDraft.items" :key="item.id"><div class="flex items-center gap-2 rounded-lg bg-slate-50 p-2"><span class="min-w-0 flex-1 truncate text-xs text-slate-700" x-text="item.name || `Product #${item.id}`"></span><label class="sr-only" :for="`quote-quantity-${index}`">Quantity</label><input :id="`quote-quantity-${index}`" type="number" min="1" max="100000" step="1" x-model.number="item.quantity" @input="quotePreview = null" class="w-20 rounded border-slate-300 py-1 text-xs"><button type="button" @click="removeQuoteProduct(index)" class="px-1 text-xs text-rose-600">Remove</button></div></template></div><button type="button" @click="previewQuote()" :disabled="quoteLoading || !quoteDraft.items.length || !quoteDraft.customer_name.trim()" class="mt-3 rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50" x-text="quoteLoading ? 'Loading…' : 'Preview quotation'"></button></div>
                                        <div x-show="quotePreview && !quote?.quotation" class="mt-3 rounded-lg border border-slate-200 p-3"><p class="font-medium text-slate-800" x-text="quotePreview?.customer_name"></p><template x-for="item in quotePreview?.items || []" :key="item.id"><div class="flex justify-between gap-2 py-1 text-xs text-slate-600"><span x-text="`${item.name} · ${item.quantity} × ₱${Number(item.unit_price).toLocaleString(undefined, { minimumFractionDigits: 2 })}`"></span><span x-text="`₱${Number(item.subtotal).toLocaleString(undefined, { minimumFractionDigits: 2 })}`"></span></div></template><div class="mt-2 flex justify-between border-t border-slate-200 pt-2 text-sm font-semibold"><span>Total (PHP)</span><span x-text="`₱${Number(quotePreview?.total || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}`"></span></div><button type="button" @click="confirmQuote()" :disabled="quoteLoading" class="mt-3 rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white"><x-ai-icon name="check" size="13" class="mr-1 inline" /> Confirm and save draft</button></div>
                                        <div x-show="quote?.quotation" class="mt-3 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900"><p x-text="`Saved as ${quote?.quotation?.quote_number || 'quotation draft'}`"></p><a :href="quote?.quotation?.print_url" target="_blank" class="mt-2 inline-flex items-center gap-1 font-semibold underline">Open printable quotation <x-ai-icon name="external" size="14" /></a></div><p x-show="quoteError" x-text="quoteError" role="alert" class="mt-2 text-xs text-rose-700"></p>
                                    </section>
                                    <div x-show="openingConversation" class="space-y-4 py-4"><div class="h-20 animate-pulse rounded-2xl bg-white"></div><div class="ml-auto h-16 max-w-[70%] animate-pulse rounded-2xl bg-blue-50"></div><div class="h-24 max-w-[80%] animate-pulse rounded-2xl bg-white"></div></div>
                                    <div x-show="loading" class="flex items-center gap-3 pb-4 text-xs font-medium text-slate-500"><span class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#203d83] text-white"><x-ai-icon name="sparkles" size="15" /></span><span>Putting the details together</span><span class="flex gap-1"><i class="h-1.5 w-1.5 animate-bounce rounded-full bg-blue-500 [animation-delay:-.3s] motion-reduce:animate-none"></i><i class="h-1.5 w-1.5 animate-bounce rounded-full bg-blue-500 [animation-delay:-.15s] motion-reduce:animate-none"></i><i class="h-1.5 w-1.5 animate-bounce rounded-full bg-blue-500 motion-reduce:animate-none"></i></span></div>
                                    <div x-show="error" class="mb-4 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-sm text-rose-800"><x-ai-icon name="help" size="18" class="mt-0.5 shrink-0" /><div class="min-w-0 flex-1"><p x-text="error" aria-live="polite"></p><button x-show="lastPrompt" type="button" @click="send(lastPrompt)" class="mt-2 text-xs font-semibold underline">Try again</button></div></div>
                                </div>
                            </div>

                            <form @submit.prevent="submit()" class="border-t border-slate-200/80 bg-white/95 px-4 py-3 shadow-[0_-6px_20px_rgba(27,42,74,0.035)] sm:px-7 sm:py-4">
                                <div class="mx-auto max-w-[900px]">
                                    <div class="flex items-end gap-2 rounded-2xl border border-slate-300 bg-white px-3 py-2 shadow-sm transition focus-within:border-[#6e88e8] focus-within:ring-4 focus-within:ring-blue-100/70 sm:px-4">
                                        <textarea id="assistant-message" x-model="draft" @keydown.enter.prevent="if (!$event.shiftKey) submit()" rows="1" maxlength="4000" :disabled="loading || status !== 'ready'" :placeholder="status === 'ready' ? 'Ask a question about this page or your work…' : statusDetails()" class="max-h-36 min-h-[48px] flex-1 resize-y border-0 bg-transparent py-2 text-sm leading-6 text-slate-800 placeholder:text-slate-400 focus:ring-0 disabled:opacity-60 sm:min-h-[52px]"></textarea>
                                        <button type="submit" :disabled="loading || status !== 'ready' || !draft.trim()" class="mb-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#203d83] text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-[#172f6e] focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-not-allowed disabled:opacity-40 motion-reduce:transform-none" aria-label="Send message"><x-ai-icon name="send" size="18" /></button>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between gap-2 px-1 text-[10px] text-slate-400"><span>Enter to send <span class="px-1">·</span> Shift + Enter for a new line</span><span class="hidden sm:inline">AI responses can be wrong. Verify important records.</span></div>
                                </div>
                            </form>
                        </main>

                        <aside class="hidden min-h-0 flex-col border-l border-slate-200 bg-white 2xl:flex">
                            <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-5"><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-800"><x-ai-icon name="compass" size="19" /></span><div><h2 class="text-sm font-bold text-slate-800">Page context</h2><p class="text-[10px] text-slate-500">Guidance for this workspace</p></div></div>
                            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-5">
                                <div><p class="text-[10px] font-bold uppercase tracking-[0.13em] text-slate-400">You’re in</p><h3 class="mt-1 text-sm font-semibold text-slate-800" x-text="pageTitle()"></h3><p class="mt-2 text-xs leading-5 text-slate-600" x-text="pageDescription()"></p></div>
                                <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-3.5"><div class="flex items-center gap-2 text-xs font-semibold text-[#284588]"><x-ai-icon name="shield" size="15" /> Access-aware answers</div><p class="mt-1.5 text-[11px] leading-5 text-blue-900/70">The assistant follows your current company and account permissions.</p></div>
                                <div><h3 class="mb-2 text-[10px] font-bold uppercase tracking-[0.13em] text-slate-400">Try asking</h3><div class="space-y-1.5"><template x-for="action in quickActions().slice(0, 4)" :key="`side-${action.title}`"><button type="button" @click="send(action.prompt)" :disabled="status !== 'ready'" class="flex w-full items-start gap-2.5 rounded-xl p-2.5 text-left transition hover:bg-slate-50 disabled:opacity-50"><span class="mt-0.5 text-blue-700"><x-ai-icon name="search" size="15" x-show="action.icon === 'search'" /><x-ai-icon name="tag" size="15" x-show="action.icon === 'tag'" /><x-ai-icon name="document" size="15" x-show="action.icon === 'document'" /><x-ai-icon name="activity" size="15" x-show="action.icon === 'activity'" /><x-ai-icon name="help" size="15" x-show="action.icon === 'help'" /></span><span><span class="block text-xs font-semibold text-slate-700" x-text="action.title"></span><span class="mt-0.5 block text-[10px] leading-4 text-slate-500" x-text="action.subtitle"></span></span></button></template></div></div>
                                <div x-show="context?.links?.length"><h3 class="mb-2 text-[10px] font-bold uppercase tracking-[0.13em] text-slate-400">Useful pages</h3><div class="space-y-1"><template x-for="link in context.links || []" :key="`link-${link.url}`"><a :href="link.url" class="flex items-center justify-between gap-2 rounded-lg px-2.5 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-blue-800"><span x-text="link.label"></span><span><x-ai-icon name="chart" size="13" x-show="/dashboard|report|analytics/i.test(link.label)" /><x-ai-icon name="external" size="13" x-show="!/dashboard|report|analytics/i.test(link.label)" /></span></a></template></div></div>
                                <div x-show="context?.modules?.length" class="border-t border-slate-100 pt-4"><p class="mb-2 text-[10px] font-bold uppercase tracking-[0.13em] text-slate-400">Available areas</p><div class="flex flex-wrap gap-1.5"><template x-for="module in context.modules || []" :key="module.key"><span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] text-slate-600" x-text="module.label"></span></template></div></div>
                            </div>
                            <div class="border-t border-slate-100 px-5 py-4 text-[10px] leading-4 text-slate-400">Page form values are never attached automatically.</div>
                        </aside>

                        <div x-show="mobileSidebarOpen" x-transition.opacity class="fixed inset-0 z-[130] bg-slate-950/40 xl:hidden" @click.self="mobileSidebarOpen = false">
                            <aside x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="flex h-full w-[min(310px,88vw)] flex-col bg-[#14233c] p-4 text-white shadow-2xl">
                                <div class="mb-5 flex items-center justify-between"><div class="flex items-center gap-2 font-semibold"><x-ai-icon name="message" size="17" /> Conversations</div><button type="button" @click="mobileSidebarOpen = false" aria-label="Close conversations"><x-ai-icon name="close" /></button></div>
                                <button type="button" @click="newConversation(); mobileSidebarOpen = false" class="mb-4 flex items-center justify-center gap-2 rounded-xl bg-white/10 px-3 py-3 text-sm font-semibold"><x-ai-icon name="plus" size="17" /> Start a new chat</button>
                                <label class="mb-4 flex items-center gap-2 rounded-xl border border-white/10 bg-white/[0.06] px-3 py-2"><x-ai-icon name="search" size="16" /><span class="sr-only">Search conversations</span><input x-model="conversationSearch" type="search" placeholder="Search conversations" class="w-full border-0 bg-transparent p-0 text-xs text-white placeholder:text-slate-500 focus:ring-0"></label>
                                <div class="min-h-0 flex-1 space-y-1 overflow-y-auto"><template x-for="item in filteredConversations()" :key="`mobile-${item.id}`"><button type="button" @click="load(item.id); mobileSidebarOpen = false" class="block w-full truncate rounded-lg px-3 py-3 text-left text-xs text-slate-200 hover:bg-white/10" x-text="item.title"></button></template></div>
                            </aside>
                        </div>
                        <div x-show="contextPanelOpen" x-transition.opacity class="fixed inset-0 z-[130] bg-slate-950/40 2xl:hidden" @click.self="contextPanelOpen = false">
                            <aside x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="ml-auto flex h-full w-[min(340px,90vw)] flex-col overflow-y-auto bg-white shadow-2xl">
                                <div class="flex items-center justify-between border-b border-slate-100 p-5"><div class="flex items-center gap-2 font-semibold text-slate-800"><x-ai-icon name="compass" /> Page context</div><button type="button" @click="contextPanelOpen = false" aria-label="Close context panel"><x-ai-icon name="close" /></button></div>
                                <div class="space-y-5 p-5"><div><h3 class="font-semibold text-slate-800" x-text="pageTitle()"></h3><p class="mt-2 text-xs leading-5 text-slate-600" x-text="pageDescription()"></p></div><template x-for="action in quickActions()" :key="`mobile-action-${action.title}`"><button type="button" @click="send(action.prompt); contextPanelOpen = false" class="flex w-full items-start gap-3 rounded-xl border border-slate-200 p-3 text-left"><span class="text-blue-700"><x-ai-icon name="search" size="18" x-show="action.icon === 'search'" /><x-ai-icon name="tag" size="18" x-show="action.icon === 'tag'" /><x-ai-icon name="document" size="18" x-show="action.icon === 'document'" /><x-ai-icon name="activity" size="18" x-show="action.icon === 'activity'" /><x-ai-icon name="help" size="18" x-show="action.icon === 'help'" /></span><span><span class="block text-xs font-semibold text-slate-700" x-text="action.title"></span><span class="mt-1 block text-[10px] text-slate-500" x-text="action.subtitle"></span></span></button></template></div>
                            </aside>
                        </div>
                    </div>
                </section>
            @else
            <section x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-[111] bg-slate-950/30 sm:bg-transparent" @click.self="if (!workspace) close()" :class="workspace ? 'sm:inset-0' : ''">
                <div class="fixed inset-x-0 bottom-0 flex h-[min(88vh,760px)] flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:inset-auto sm:bottom-6 sm:right-6 sm:h-[min(720px,calc(100vh-3rem))] sm:w-[min(440px,calc(100vw-3rem))] sm:rounded-2xl" :class="workspace ? 'sm:!inset-6 sm:!h-auto sm:!w-auto sm:rounded-2xl' : ''" role="dialog" aria-modal="true" aria-label="Project Tracker Assistant">
                    <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-5 py-4">
                        <div class="flex min-w-0 items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#203d83] text-white"><x-ai-icon name="sparkles" size="19" /></span><div class="min-w-0"><div class="flex items-center gap-2"><h2 class="truncate text-base font-bold tracking-tight text-slate-900">AI Assistant</h2><span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700" x-text="context?.label || company || 'Workspace'"></span></div><p class="mt-1 truncate text-xs text-slate-500" x-text="context?.page?.title || 'Context-aware workspace help'"></p></div></div>
                        <div class="flex shrink-0 items-center gap-2">
                            <button x-show="conversationId" type="button" @click="deleteConversation()" class="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:bg-rose-50 hover:text-rose-700" aria-label="Delete chat"><x-ai-icon name="trash" size="17" /></button>
                            <a x-show="!workspace" :href="routes.full" class="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-blue-700" aria-label="Open fullscreen assistant"><x-ai-icon name="expand" size="17" /></a>
                            <button x-show="!workspace" type="button" @click="close()" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100" aria-label="Close assistant"><x-ai-icon name="close" size="17" /></button>
                        </div>
                    </header>

                    <div x-show="status !== 'ready'" role="status" aria-live="polite" class="flex items-start gap-3 border-b border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-950"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-800"><x-ai-icon name="sparkles" size="17" /></span><span class="min-w-0"><strong class="block font-semibold" x-text="statusTitle()"></strong><span class="mt-0.5 block text-xs leading-5 text-amber-900/80" x-text="statusDetails()"></span><a x-show="status === 'not_configured' && routes.aiSettings" :href="routes.aiSettings" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold underline"><x-ai-icon name="external" size="13" /> Open configuration</a></span></div>

                    <div class="grid min-h-0 flex-1 grid-cols-1 md:grid-cols-[190px_minmax(0,1fr)]">
                        <aside class="hidden border-r border-slate-200 bg-slate-50 p-3 md:flex md:flex-col">
                            <button type="button" @click="newConversation()" :disabled="status !== 'ready'" class="mb-3 inline-flex items-center justify-center gap-2 rounded-xl bg-[#203d83] px-3 py-2.5 text-sm font-semibold text-white transition hover:bg-[#172f6e] disabled:cursor-not-allowed disabled:opacity-50"><x-ai-icon name="plus" size="16" /> New conversation</button>
                            <label class="mb-3 flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-slate-400 focus-within:border-blue-300"><x-ai-icon name="search" size="15" /><span class="sr-only">Search conversations</span><input x-model="conversationSearch" type="search" placeholder="Search chats" class="w-full border-0 bg-transparent p-0 text-xs text-slate-700 placeholder:text-slate-400 focus:ring-0"></label>
                            <p class="mb-2 flex items-center gap-1.5 px-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500"><x-ai-icon name="clock" size="13" /> Recent chats</p>
                            <div class="min-h-0 space-y-1 overflow-y-auto">
                                <template x-for="item in filteredConversations()" :key="item.id">
                                    <button type="button" @click="load(item.id)" class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-xs transition hover:bg-white" :class="Number(conversationId) === Number(item.id) ? 'bg-white font-semibold text-slate-900 shadow-sm' : 'text-slate-600'"><x-ai-icon name="message" size="14" class="shrink-0 text-slate-400" /><span class="min-w-0 flex-1 truncate" x-text="item.title"></span><span class="shrink-0 text-[10px] text-slate-400" x-text="formatTime(item.last_message_at)"></span></button>
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
                                <template x-if="messages.length === 0">
                                    <div class="rounded-2xl border border-blue-100 bg-gradient-to-br from-[#f4f7ff] to-white p-4 text-sm leading-6 text-slate-700 shadow-sm">
                                        <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#203d83] text-white"><x-ai-icon name="sparkles" size="18" /></span><div><p class="font-semibold text-slate-900" x-text="welcomeTitle()"></p><p class="mt-0.5 text-xs text-slate-500" x-text="pageTitle()"></p></div></div><p class="mt-3" x-text="pageDescription()"></p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <template x-for="suggestion in context.suggestions || []" :key="suggestion"><button type="button" @click="send(suggestion)" class="rounded-full border border-blue-200 bg-white px-3 py-1.5 text-xs text-blue-800 hover:bg-blue-100" x-text="suggestion"></button></template>
                                        </div>
                                        <div x-show="context.links?.length" class="mt-3 flex flex-wrap gap-2 border-t border-blue-100 pt-3">
                                            <template x-for="link in context.links || []" :key="link.url"><a :href="link.url" class="text-xs font-medium text-blue-700 underline" x-text="link.label"></a></template>
                                        </div>
                                    </div>
                                </template>
                                <template x-for="(message, index) in messages" :key="index">
                                    <div class="flex items-start gap-2.5" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                                        <span x-show="message.role !== 'user'" class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#203d83] text-white"><x-ai-icon name="sparkles" size="14" /></span>
                                        <div class="max-w-[92%] min-w-0 rounded-2xl px-3.5 py-2.5 text-sm leading-6 whitespace-pre-wrap" :class="message.role === 'user' ? 'bg-blue-50 text-slate-900' : 'border border-slate-200 bg-white text-slate-800'">
                                            <div class="mb-1 text-[10px] font-semibold text-slate-500" x-text="`${message.role === 'user' ? 'You' : 'Assistant'} · ${formatTime(message.created_at)}`"></div>
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
                                <div class="flex items-end gap-2 rounded-2xl border border-slate-300 bg-white p-2 shadow-sm transition focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-100/70">
                                    <textarea id="assistant-message" x-model="draft" @keydown.enter.prevent="if (!$event.shiftKey) submit()" rows="2" maxlength="4000" :disabled="loading || status !== 'ready'" :placeholder="status === 'ready' ? 'Ask about this page or your workspace…' : statusMessage()" class="max-h-32 min-h-[48px] flex-1 resize-y border-0 bg-transparent text-sm text-slate-800 placeholder:text-slate-400 focus:ring-0 disabled:opacity-60"></textarea>
                                    <button type="submit" :disabled="loading || status !== 'ready' || !draft.trim()" class="mb-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#203d83] text-white transition hover:-translate-y-0.5 hover:bg-[#172f6e] disabled:cursor-not-allowed disabled:opacity-50 motion-reduce:transform-none" aria-label="Send message"><x-ai-icon name="send" size="17" /></button>
                                </div>
                                <p class="mt-2 flex items-center justify-between gap-2 px-1 text-[10px] text-slate-400"><span>Enter to send <span class="px-1">·</span> Shift + Enter for a new line</span><span class="hidden sm:inline">Page form values are not shared automatically.</span></p>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
            @endif
        </div>
    @endif
@endauth
