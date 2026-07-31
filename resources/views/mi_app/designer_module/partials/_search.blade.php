{{-- ============================= --}}
{{-- Search & Filter --}}
{{-- ============================= --}}
<div class="bg-white rounded-xl border border-slate-200 p-5">

    <form action="{{ route('mi_app.index') }}"
          method="GET"
          class="flex flex-col lg:flex-row lg:items-center gap-3">

        {{-- Search --}}
        <div class="flex-1 relative">

            <svg xmlns="http://www.w3.org/2000/svg"
                 class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor">

                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M21 21l-5.2-5.2m2.2-5.3a7 7 0 11-14 0 7 7 0 0114 0z" />

            </svg>

            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search item name, category, collection, designer..."
                class="w-full rounded-lg border border-slate-300 pl-10 pr-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100">

        </div>

        {{-- Status --}}
        <div class="w-full lg:w-48">

            <select
                name="status"
                class="w-full rounded-lg border border-slate-300 py-2.5 px-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100">

                <option value="">All Status</option>

                <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>
                    Active
                </option>

                <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>
                    Inactive
                </option>

            </select>

        </div>

        {{-- Classification --}}
        <div class="w-full lg:w-52">

            <select
                name="classification"
                class="w-full rounded-lg border border-slate-300 py-2.5 px-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100">

                <option value="">All Classification</option>

                <option value="Indoor" {{ request('classification') == 'Indoor' ? 'selected' : '' }}>
                    Indoor
                </option>

                <option value="Outdoor" {{ request('classification') == 'Outdoor' ? 'selected' : '' }}>
                    Outdoor
                </option>

                <option value="Office" {{ request('classification') == 'Office' ? 'selected' : '' }}>
                    Office
                </option>

                <option value="Residential" {{ request('classification') == 'Residential' ? 'selected' : '' }}>
                    Residential
                </option>

            </select>

        </div>

        {{-- Buttons --}}
        <div class="flex gap-2">

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">

                Search

            </button>

            @if(request()->filled('search') || request()->filled('status') || request()->filled('classification'))

                <a href="{{ route('mi_app.index') }}"
                   class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100">

                    Clear

                </a>

            @endif

        </div>

    </form>

</div>