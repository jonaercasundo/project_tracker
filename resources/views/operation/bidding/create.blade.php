<x-project_app-layout>

    <x-slot name="header">
        <div class="flex flex-col">
            <h1 class="text-base font-black text-slate-900 tracking-tight sm:text-lg">
                Create Bidding Document
            </h1>

            <p class="text-[11px] font-semibold text-slate-400 tracking-wide uppercase mt-0.5">
                Procurement & Bidding Management
            </p>
        </div>
    </x-slot>

    <x-slot name="headerActions">

        <a href="{{ route('bidding.index') }}"
           class="inline-flex items-center px-4 py-2 text-xs font-bold uppercase bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition">

            <svg class="w-4 h-4 mr-2"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">

                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 19l-7-7 7-7"/>

            </svg>

            Back

        </a>

    </x-slot>

    <form action="{{ route('bidding.store') }}"
          method="POST">

        @csrf

        @include('finance.bidding.partials._forms')

    </form>
<script>

const region = document.getElementById('region');
const province = document.getElementById('province');
const city = document.getElementById('city');
const barangay = document.getElementById('barangay');

async function loadRegions(){

    const res = await fetch('/api/regions');
    const data = await res.json();

    let html = '<option value="">Select Region</option>';

    data.forEach(r => {
        html += `<option value="${r.code}">${r.name}</option>`;
    });

    region.innerHTML = html;
}

async function loadProvinces(regionCode){

    const res = await fetch('/api/provinces?region=' + regionCode);
    const data = await res.json();

    let html = '<option value="">Select Province</option>';

    data.forEach(r => {
        html += `<option value="${r.code}">${r.name}</option>`;
    });

    province.innerHTML = html;
}

async function loadCities(provinceCode){

    const res = await fetch('/api/cities?province=' + provinceCode);
    const data = await res.json();

    let html = '<option value="">Select City</option>';

    data.forEach(r => {
        html += `<option value="${r.code}">${r.name}</option>`;
    });

    city.innerHTML = html;
}

async function loadBarangays(cityCode){

    const res = await fetch('/api/barangays?city=' + cityCode);
    const data = await res.json();

    let html = '<option value="">Select Barangay</option>';

    data.forEach(r => {
        html += `<option value="${r.code}">${r.name}</option>`;
    });

    barangay.innerHTML = html;
}

loadRegions();

region.addEventListener('change', function () {
    loadProvinces(this.value);
});

province.addEventListener('change', function () {
    loadCities(this.value);
});

city.addEventListener('change', function () {
    loadBarangays(this.value);
});

</script>


<script>

function recalculate(el)
{
    let row = el.closest('.item-row');

    let qty = parseFloat(row.querySelector('.qty').value) || 0;
    let cost = parseFloat(row.querySelector('.unit-cost').value) || 0;

    let total = qty * cost;

    row.querySelector('.total').value = formatNumber(total);

    updateGrandTotal();
}

function updateGrandTotal()
{
    let totals = document.querySelectorAll('.total');

    let grand = 0;

    totals.forEach(el => {
        let val = parseFloat(el.value.replace(/,/g, '')) || 0;
        grand += val;
    });

    let grandEl = document.getElementById('grand-total');

    if (grandEl) {
        grandEl.innerText = formatNumber(grand);
    }
}

function formatNumber(num)
{
    return parseFloat(num).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

</script>
<script>

document.addEventListener('DOMContentLoaded', function () {
    updateGrandTotal();
});

</script>
</x-project_app-layout>