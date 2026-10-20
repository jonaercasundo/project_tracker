<x-finance_app-layout>
    @php
        $values = old('inputs', $inputValues ?? $defaultInputs);
        $rates = old('assumptions', $assumptionValues ?? $defaultAssumptions);
        $existingAdditionalCosts = old('cost_items', $additionalCostItems ?? []);
        $isEditing = isset($plan);
        $inputFields = [
            'contract_amount' => ['Award / Contract Amount', 'money'],
            'abc' => ['Approved Budget for the Contract (ABC)', 'money'],
            'factory_cost' => ['Factory Cost (PHP)', 'money'],
            'pf1_rate' => ['PF1 rate', 'rate'],
            'pf2_rate' => ['PF2 rate', 'rate'],
            'pf3_rate' => ['PF3 rate', 'rate'],
            'll_commission_rate' => ['LL commission rate', 'rate'],
            'interest_dst_rate' => ['Working capital interest & DST rate', 'rate'],
            'shipping_containers' => ['Shipping container count', 'whole'],
            'rate_per_container' => ['Rate per container (PHP)', 'money'],
            'logistics_rate' => ['Logistics rate against ABC', 'rate'],
            'warehouse_area' => ['Warehouse area (sqm)', 'number'],
            'warehouse_rent_per_sqm' => ['Monthly rent per sqm (PHP)', 'money'],
            'warehouse_months' => ['Warehouse rental months', 'whole'],
            'other_expenses_rate' => ['Other expenses rate against contract', 'rate'],
            'manpower_rate' => ['Manpower rate against ABC', 'rate'],
            'assembly_rate' => ['Assembly / service center rate against ABC', 'rate'],
            'rd_rate' => ['R&D rate against ABC', 'rate'],
            'facilitation_rate' => ['Facilitation rate against ABC', 'rate'],
            'tax_lawyer_rate' => ['Tax lawyer allowance rate against net sales', 'rate'],
            'opex_rate' => ['OPEX rate against net sales', 'rate'],
            'income_tax_rate' => ['Income tax provision rate against net sales', 'rate'],
            'incentive_rate' => ['Incentive rate against net profit', 'rate'],
            'importation_vat_rate' => ['Importation VAT rate against factory cost', 'rate'],
            'sales_vat_rate' => ['Output VAT rate included in contract amount', 'rate'],
        ];
        $assumptionFields = [
            'municipal_first_rate' => 'Municipal tax rate on first ₱400,000',
            'municipal_excess_rate' => 'Municipal tax rate above ₱400,000',
            'rrdm_rate' => 'RRDM rate',
            'bondable_rate' => 'Bondable amount rate against contract',
            'bond_premium_rate' => 'Performance bond premium rate',
            'bond_dst_rate' => 'Bond documentary stamp tax rate',
            'bond_lgt_rate' => 'Bond local government tax rate',
            'bond_notarial_fee' => 'Bond notarial fee per lot (PHP)',
            'bond_service_fee' => 'Bond service fee per lot (PHP)',
            'marine_premium_rate' => 'Marine cargo premium rate',
            'marine_dst_rate' => 'Marine cargo DST rate',
            'marine_lgt_rate' => 'Marine cargo local government tax rate',
            'fire_premium_rate' => 'Fire premium rate',
            'fire_dst_rate' => 'Fire DST rate',
            'fire_tax_rate' => 'Fire tax rate',
            'fire_lgt_rate' => 'Fire local government tax rate',
            'inland_premium_rate' => 'Inland marine premium rate',
            'inland_dst_rate' => 'Inland marine DST rate',
            'inland_lgt_rate' => 'Inland marine local government tax rate',
        ];
    @endphp

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Project finance · draft</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ $isEditing ? 'Revise financial draft' : 'New financial draft' }}</h1>
                <p class="mt-1 text-sm text-slate-500">A save creates a preserved version. Drafts do not change project implementation status.</p>
            </div>
            <a href="{{ $isEditing ? route('finance.plans.show', $plan) : route('finance.plans.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">Cancel</a>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                <p class="font-semibold">Check the highlighted financial plan fields.</p>
                <ul class="mt-2 list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form id="financial-plan-form" method="POST" action="{{ $isEditing ? route('finance.plans.update', $plan) : route('finance.plans.store') }}" class="space-y-6">
            @csrf
            @if ($isEditing) @method('PUT') @endif

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Plan record</h2>
                        <p class="mt-1 text-sm text-slate-500">Link an existing project or bidding lot, or keep this as a standalone pre-award plan.</p>
                    </div>
                    @if ($isEditing)<span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">Next version: {{ (int) $plan->versions()->max('version_number') + 1 }}</span>@endif
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700">Plan name <span class="text-rose-600">*</span></label>
                        <input id="name" name="name" required maxlength="255" value="{{ old('name', $plan->name ?? '') }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                        @error('name')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="project_id" class="mb-1.5 block text-sm font-medium text-slate-700">Implementation project</label>
                        <select id="project_id" name="project_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                            <option value="">Standalone / not linked</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->project_id }}" data-abc="{{ $project->ABC ?? '' }}" data-contract-amount="{{ $project->contract_amount ?? '' }}" @selected((string) old('project_id', $plan->project_id ?? '') === (string) $project->project_id)>{{ $project->project_code }} · {{ $project->project_name }}</option>
                            @endforeach
                        </select>
                        @error('project_id')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="project_information_id" class="mb-1.5 block text-sm font-medium text-slate-700">Bidding document</label>
                        <select id="project_information_id" name="project_information_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                            <option value="">No bidding document</option>
                            @foreach ($biddingDocuments as $document)
                                <option value="{{ $document->id }}" data-abc="{{ $document->approved_budget_contract_abc ?? '' }}" data-contract-amount="{{ $document->contract_amount ?? '' }}" @selected((string) old('project_information_id', $plan->project_information_id ?? '') === (string) $document->id)>{{ $document->project_id }} · {{ $document->project_name }} ({{ $document->status }})</option>
                            @endforeach
                        </select>
                        @error('project_information_id')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="lot_id" class="mb-1.5 block text-sm font-medium text-slate-700">Optional bidding lot</label>
                        <select id="lot_id" name="lot_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                            <option value="">No lot selected</option>
                            @foreach ($lots as $lot)
                                <option value="{{ $lot->id }}" @selected((string) old('lot_id', $plan->lot_id ?? '') === (string) $lot->id)>{{ $lot->project?->project_name }} · Lot {{ $lot->lot_no }}</option>
                            @endforeach
                        </select>
                        @error('lot_id')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="ppl_form_id" class="mb-1.5 block text-sm font-medium text-slate-700">Source PPL record</label>
                        <select id="ppl_form_id" name="ppl_form_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                            <option value="">No historical PPL source</option>
                            @foreach ($pplForms as $pplForm)
                                <option value="{{ $pplForm->id }}" data-abc="{{ $pplForm->abc ?? '' }}" @selected((string) old('ppl_form_id', $plan->ppl_form_id ?? '') === (string) $pplForm->id)>{{ $pplForm->project_code }} / Lot {{ $pplForm->lot_number }} · {{ $pplForm->project_title }}</option>
                            @endforeach
                        </select>
                        @error('ppl_form_id')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2 rounded-lg border border-emerald-100 bg-emerald-50 p-4">
                        <p class="text-sm text-emerald-950">Blank ABC and contract amount fields can be filled from linked records. Bidding values take priority, followed by implementation project values; historical PPL ABC is used when neither provides ABC. Existing entries are kept. Factory cost and rates still need verified project-specific values.</p>
                        <button type="button" id="populate-linked-financial-inputs" class="mt-3 rounded-lg border border-emerald-700 bg-white px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-100">Fill blank linked values</button>
                    </div>
                    <div class="md:col-span-2">
                        <label for="change_summary" class="mb-1.5 block text-sm font-medium text-slate-700">Version note</label>
                        <input id="change_summary" name="change_summary" maxlength="255" value="{{ old('change_summary') }}" placeholder="What changed in this draft?" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-5">
                    <h2 class="text-base font-semibold text-slate-950">PPL financial inputs</h2>
                    <p class="mt-1 text-sm text-slate-500">Enter the project-specific values from its PPL form. Rates are fractions (0.05 means 5%). Use 0 only when Finance confirms an item does not apply; project-specific rates are not prefilled.</p>
                </div>
                <div class="grid gap-x-5 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($inputFields as $key => [$label, $type])
                        @php($step = $type === 'rate' ? '0.000001' : ($type === 'whole' ? '1' : ($type === 'money' ? '0.01' : '0.01')))
                        <div>
                            <label for="input-{{ $key }}" class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }} <span class="text-rose-600">*</span></label>
                            <input id="input-{{ $key }}" name="inputs[{{ $key }}]" type="number" step="{{ $step }}" min="0" required value="{{ $values[$key] ?? '' }}" class="w-full rounded-lg border-slate-300 text-sm tabular-nums shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                            @error('inputs.'.$key)<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                    @if ($factoryCosts->isNotEmpty())
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label for="factory_cost_id" class="mb-1.5 block text-sm font-medium text-slate-700">Factory cost source snapshot (MI)</label>
                            <select id="factory_cost_id" name="inputs[factory_cost_id]" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                                <option value="">Use the factory cost entered above</option>
                                @foreach ($factoryCosts as $factoryCost)
                                    <option value="{{ $factoryCost->id }}" @selected((string) ($values['factory_cost_id'] ?? '') === (string) $factoryCost->id)>{{ $factoryCost->product?->item_name }} · {{ $factoryCost->factory_name }} · {{ $factoryCost->currency }} {{ number_format((float) $factoryCost->factory_cost, 2) }} (snapshot only)</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <details class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <summary class="cursor-pointer text-sm font-semibold text-slate-800">Workbook rate assumptions</summary>
                    <p class="mt-1 text-xs text-slate-500">Insurance rates exclude VAT on premiums, as stated in the workbook. All values below are saved per version.</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($assumptionFields as $key => $label)
                            @php($wholeValue = in_array($key, ['bond_notarial_fee','bond_service_fee'], true))
                            <div>
                                <label for="assumption-{{ $key }}" class="mb-1.5 block text-xs font-medium text-slate-700">{{ $label }}</label>
                                <input id="assumption-{{ $key }}" name="assumptions[{{ $key }}]" type="number" min="0" step="{{ $wholeValue ? '0.01' : '0.000001' }}" value="{{ $rates[$key] ?? '' }}" class="w-full rounded-lg border-slate-300 bg-white text-sm tabular-nums shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                                @error('assumptions.'.$key)<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                </details>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Additional cost details</h2>
                        <p class="mt-1 text-sm text-slate-500">Record detailed items and source references without changing the workbook category formulas.</p>
                    </div>
                    <button type="button" id="add-cost-row" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Add cost item</button>
                </div>
                <div id="cost-rows" class="space-y-3">
                    @foreach ($existingAdditionalCosts as $index => $cost)
                        <div class="grid gap-3 rounded-lg border border-slate-200 p-3 sm:grid-cols-[1.4fr_1fr_1fr_auto]" data-cost-row>
                            <input name="cost_items[{{ $index }}][label]" value="{{ $cost['label'] ?? '' }}" placeholder="Cost item" aria-label="Cost item" class="rounded-lg border-slate-300 text-sm shadow-sm">
                            <input name="cost_items[{{ $index }}][amount]" value="{{ $cost['amount'] ?? '' }}" type="number" step="0.01" min="0" placeholder="Amount (PHP)" aria-label="Amount (PHP)" class="rounded-lg border-slate-300 text-sm tabular-nums shadow-sm">
                            <input name="cost_items[{{ $index }}][source_reference]" value="{{ $cost['source_reference'] ?? '' }}" placeholder="Source / reference" aria-label="Source / reference" class="rounded-lg border-slate-300 text-sm shadow-sm">
                            <button type="button" data-remove-cost class="rounded-lg px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Remove</button>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ $isEditing ? route('finance.plans.show', $plan) : route('finance.plans.index') }}" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">{{ $isEditing ? 'Save new version' : 'Save financial draft' }}</button>
            </div>
        </form>
    </div>

    <template id="cost-row-template">
        <div class="grid gap-3 rounded-lg border border-slate-200 p-3 sm:grid-cols-[1.4fr_1fr_1fr_auto]" data-cost-row>
            <input data-name="label" placeholder="Cost item" aria-label="Cost item" class="rounded-lg border-slate-300 text-sm shadow-sm">
            <input data-name="amount" type="number" step="0.01" min="0" placeholder="Amount (PHP)" aria-label="Amount (PHP)" class="rounded-lg border-slate-300 text-sm tabular-nums shadow-sm">
            <input data-name="source_reference" placeholder="Source / reference" aria-label="Source / reference" class="rounded-lg border-slate-300 text-sm shadow-sm">
            <button type="button" data-remove-cost class="rounded-lg px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Remove</button>
        </div>
    </template>
    <script>
        (() => {
            const list = document.getElementById('cost-rows');
            const template = document.getElementById('cost-row-template');
            const populateButton = document.getElementById('populate-linked-financial-inputs');
            let nextIndex = list.querySelectorAll('[data-cost-row]').length;

            populateButton.addEventListener('click', () => {
                const biddingOption = document.querySelector('#project_information_id').selectedOptions[0];
                const projectOption = document.querySelector('#project_id').selectedOptions[0];
                const pplFormOption = document.querySelector('#ppl_form_id').selectedOptions[0];
                const sources = {
                    abc: [biddingOption?.dataset.abc, projectOption?.dataset.abc, pplFormOption?.dataset.abc],
                    contract_amount: [biddingOption?.dataset.contractAmount, projectOption?.dataset.contractAmount],
                };

                for (const [key, values] of Object.entries(sources)) {
                    const input = document.getElementById(`input-${key}`);
                    if (input.value !== '') {
                        continue;
                    }

                    const value = values.find((candidate) => candidate !== undefined && candidate !== '');
                    if (value !== undefined) {
                        input.value = value;
                    }
                }
            });

            document.getElementById('add-cost-row').addEventListener('click', () => {
                const row = template.content.firstElementChild.cloneNode(true);
                row.querySelectorAll('[data-name]').forEach((input) => {
                    input.name = `cost_items[${nextIndex}][${input.dataset.name}]`;
                });
                nextIndex += 1;
                list.append(row);
            });
            list.addEventListener('click', (event) => {
                if (event.target.matches('[data-remove-cost]')) {
                    event.target.closest('[data-cost-row]').remove();
                }
            });
        })();
    </script>
</x-finance_app-layout>
