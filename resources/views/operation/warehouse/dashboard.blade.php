<x-project_warehouse_app>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Inventory Scanner
                </h1>

                <p class="text-sm text-slate-500">
                    Scan packages or individual items for inventory movement.
                </p>
            </div>

            <a href="{{ route('warehouse.inventory.index') }}"
               class="inline-flex items-center px-5 py-2.5 rounded-xl bg-slate-200 text-slate-700 font-semibold hover:bg-slate-300">

                ← Back

            </a>

        </div>


        {{-- Current Mode --}}
        <div class="hidden rounded-2xl border border-indigo-200 bg-indigo-50 p-5"
             id="currentMode">

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm text-slate-500">
                        Current Transaction
                    </p>

                    <h2 class="text-xl font-bold text-indigo-700">

                        <span id="transactionText">
                            -
                        </span>

                    </h2>

                </div>

                <div>

                    <p class="text-sm text-slate-500">
                        Scan Type
                    </p>

                    <h2 class="text-xl font-bold text-indigo-700">

                        <span id="scanTypeText">
                            -
                        </span>

                    </h2>

                </div>

            </div>

        </div>


        {{-- STEP 1 --}}
        <div id="step1"
             class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

            <h2 class="text-lg font-semibold mb-6">
                Step 1 — Select Transaction
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <button
                    id="btnInventoryIn"
                    class="rounded-2xl border-2 border-green-500 bg-green-50 hover:bg-green-100 transition p-8">

                    <div class="text-5xl mb-4">

                        📥

                    </div>

                    <div class="text-2xl font-bold text-green-700">

                        Inventory In

                    </div>

                    <p class="text-slate-500 mt-2">

                        Add Quantity

                    </p>

                </button>


                <button
                    id="btnInventoryOut"
                    class="rounded-2xl border-2 border-red-500 bg-red-50 hover:bg-red-100 transition p-8">

                    <div class="text-5xl mb-4">

                        📤

                    </div>

                    <div class="text-2xl font-bold text-red-700">

                        Inventory Out

                    </div>

                    <p class="text-slate-500 mt-2">

                        Minus Quantity

                    </p>

                </button>

            </div>

        </div>


        {{-- STEP 2 --}}
        <div id="step2"
             class="hidden bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

            <h2 class="text-lg font-semibold mb-6">
                Step 2 — Select Scan Type
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <button
                    id="btnPackage"
                    class="rounded-2xl border-2 border-blue-500 bg-blue-50 hover:bg-blue-100 transition p-8">

                    <div class="text-5xl mb-4">

                        📦

                    </div>

                    <div class="text-2xl font-bold text-blue-700">

                        Package

                    </div>

                    <p class="text-slate-500 mt-2">

                        Scan Package QR

                    </p>

                </button>


                <button
                    id="btnItem"
                    class="rounded-2xl border-2 border-amber-500 bg-amber-50 hover:bg-amber-100 transition p-8">

                    <div class="text-5xl mb-4">

                        📄

                    </div>

                    <div class="text-2xl font-bold text-amber-700">

                        Individual Item

                    </div>

                    <p class="text-slate-500 mt-2">

                        Scan Item QR

                    </p>

                </button>

            </div>

        </div>


        {{-- STEP 3 --}}
        <div id="step3"
            class="hidden space-y-6">

            {{-- Live Status --}}
            <div class="grid grid-cols-4 gap-4">

                <div class="bg-green-50 rounded-xl p-4 border">
                    <p class="text-sm text-gray-500">Scanned</p>
                    <h2 id="totalScanned" class="text-3xl font-bold">0</h2>
                </div>

                <div class="bg-blue-50 rounded-xl p-4 border">
                    <p class="text-sm text-gray-500">Success</p>
                    <h2 id="successCount" class="text-3xl font-bold text-blue-600">0</h2>
                </div>

                <div class="bg-red-50 rounded-xl p-4 border">
                    <p class="text-sm text-gray-500">Failed</p>
                    <h2 id="failedCount" class="text-3xl font-bold text-red-600">0</h2>
                </div>

                <div class="bg-yellow-50 rounded-xl p-4 border">
                    <p class="text-sm text-gray-500">Duplicate</p>
                    <h2 id="duplicateCount" class="text-3xl font-bold text-yellow-600">0</h2>
                </div>

            </div>

            {{-- Hidden Scanner --}}
            <input
                id="scannerInput"
                class="absolute -left-[9999px]"
                autocomplete="off">

            {{-- Live Table --}}
            <div class="bg-white rounded-xl border overflow-hidden">

                <table class="min-w-full text-sm">

                    <thead class="bg-slate-100">

                        <tr>

                            <th>#</th>
                            <th>Package</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Time</th>

                        </tr>

                    </thead>

                    <tbody id="scanTable"
                        class="divide-y divide-slate-200">
                    </tbody>

                </table>

            </div>

        </div>
    </div>
    @push('scripts')
<script>

let transactionType = null;
let scanType = null;

// Elements
const step1 = document.getElementById('step1');
const step2 = document.getElementById('step2');
const step3 = document.getElementById('step3');

const currentMode = document.getElementById('currentMode');

const transactionText = document.getElementById('transactionText');
const scanTypeText = document.getElementById('scanTypeText');
const scannerInput = document.getElementById('scannerInput');
const scanResult = document.getElementById('scanResult');
const resultText = document.getElementById('resultText');
let scannedCount = 0;
let successCounter = 0;
let failedCounter = 0;
let duplicateCounter = 0;

const totalScannedEl = document.getElementById('totalScanned');
const successCountEl = document.getElementById('successCount');
const failedCountEl = document.getElementById('failedCount');
const duplicateCountEl = document.getElementById('duplicateCount');
const scanTable = document.getElementById('scanTable');
const scannedList = new Set();
// ================================
// STEP 1
// ================================

document.getElementById('btnInventoryIn').addEventListener('click', function () {

    transactionType = 'IN';

    transactionText.textContent = '📥 Inventory In';

    scanTypeText.textContent = '-';

    currentMode.classList.remove('hidden');

    step1.classList.add('hidden');

    step2.classList.remove('hidden');

});

document.getElementById('btnInventoryOut').addEventListener('click', function () {

    transactionType = 'OUT';

    transactionText.textContent = '📤 Inventory Out';

    scanTypeText.textContent = '-';

    currentMode.classList.remove('hidden');

    step1.classList.add('hidden');

    step2.classList.remove('hidden');

});


// ================================
// STEP 2
// ================================

document.getElementById('btnPackage').addEventListener('click', function () {

    scanType = 'PACKAGE';

    scanTypeText.textContent = '📦 Package';

    step2.classList.add('hidden');

    step3.classList.remove('hidden');
    activateScanner();
});

document.getElementById('btnItem').addEventListener('click', function () {

    scanType = 'ITEM';

    scanTypeText.textContent = '📄 Individual Item';

    step2.classList.add('hidden');

    step3.classList.remove('hidden');
    activateScanner();
});
scannerInput.addEventListener("keydown", async function(e){

    if(e.key !== "Enter") return;

    e.preventDefault();

    const qr = scannerInput.value.trim();

    scannerInput.value = "";

    if(qr=="") return;

    if(scannedList.has(qr)){

        duplicateCounter++;

        updateDashboard();

        scannedCount++;

        duplicateCounter++;

        addRow({
            package: "-",
            item: "-",
            qty: "-",
            status: "Duplicate"
        });

        updateDashboard();

        return;

    }

    scannedList.add(qr);

    await saveInventory(qr);

});
function activateScanner() {

    scannerInput.value = "";

    scannerInput.focus();

}
scannerInput.addEventListener('blur', function () {

    setTimeout(() => {

        scannerInput.focus();

    }, 50);

});
async function processScan(qr) {

    try {

        const url = new URL(qr);

        const packageStatusId = url.searchParams.get('id');

        if (!packageStatusId) {
            alert("Invalid QR Code");
            return;
        }

        const response = await fetch('/warehouse/inventory/scan', {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },

            body: JSON.stringify({

                package_status_id: packageStatusId,

                warehouse_id: 1, // replace with selected warehouse later

                transaction: transactionType

            })

        });

        const data = await response.json();

        if (!response.ok) {

            alert(data.message ?? 'Scan failed.');

            return;

        }

        scanResult.classList.remove('hidden');

        resultText.innerHTML = `
            <strong style="color:green;">✔ ${data.message}</strong><br>
            Package Status ID: ${packageStatusId}
        `;

    } catch (e) {

        console.error(e);

        alert('Invalid QR Code');

    }

    scannerInput.value = "";

    scannerInput.focus();

}
async function saveInventory(qr){

    try{

        const response = await fetch("{{ route('warehouse.inventory.scan') }}",{

            method:"POST",

            headers:{
                "Content-Type":"application/json",
                "X-CSRF-TOKEN":"{{ csrf_token() }}"
            },

            body:JSON.stringify({

                qr:qr,
                transaction:transactionType,
                scan_type:scanType

            })

        });

        const result = await response.json();

        scannedCount++;

        if(result.success){

            successCounter++;

            addRow(result);

        }else{

            failedCounter++;

            addRow({

                package:"",
                item:"",
                qty:"",
                status:result.message

            });

        }

        updateDashboard();

    }finally{

        scannerInput.focus();

    }

}
function updateDashboard(){

    totalScannedEl.textContent = scannedCount;
    successCountEl.textContent = successCounter;
    failedCountEl.textContent = failedCounter;
    duplicateCountEl.textContent = duplicateCounter;

}
function addRow(data){

const row = `
<tr class="hover:bg-slate-50">

    <td class="px-4 py-3">${scannedCount}</td>

    <td class="px-4 py-3">${data.package ?? '-'}</td>

    <td class="px-4 py-3">${data.item ?? '-'}</td>

    <td class="px-4 py-3">${data.qty ?? '-'}</td>

    <td class="px-4 py-3">

        <span class="rounded bg-green-100 px-2 py-1 text-green-700">

            ${data.status}

        </span>

    </td>

    <td class="px-4 py-3">

        ${new Date().toLocaleTimeString()}

    </td>

</tr>
`;

    scanTable.insertAdjacentHTML("afterbegin",row);

}
</script>
@endpush
</x-project_warehouse_app>