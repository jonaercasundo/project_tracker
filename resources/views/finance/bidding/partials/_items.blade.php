<div class="grid grid-cols-6 gap-2 mb-3 item-row items-start">

    <input type="text"
        name="items[{{ $i }}][item_no]"
        value="{{ $item->item_no ?? '' }}"
        class="border p-2 text-xs rounded"
        placeholder="No">

    <textarea
    class="description w-full border p-2 text-xs rounded resize-none overflow-hidden leading-5"
        rows="1"
        oninput="console.log('typing'); autoResize(this);"
    ></textarea>

    <input type="text"
        name="items[{{ $i }}][unit]"
        value="{{ $item->unit ?? '' }}"
        class="border p-2 text-xs rounded"
        placeholder="Unit">

    {{-- QTY --}}
    <input type="number"
        name="items[{{ $i }}][quantity]"
        value="{{ $item->quantity ?? '' }}"
        class="border p-2 text-xs rounded qty"
        placeholder="Qty"
        step="any"
        oninput="recalculate(this)">

    {{-- UNIT COST --}}
    <input type="number"
        name="items[{{ $i }}][unit_cost]"
        value="{{ $item->unit_cost ?? '' }}"
        class="border p-2 text-xs rounded unit-cost"
        placeholder="Unit Cost"
        step="any"
        oninput="recalculate(this)">

    {{-- TOTAL --}}
    <input type="text"
        name="items[{{ $i }}][total_amount]"
        value="{{ $item->total_amount ?? '' }}"
        class="border p-2 text-xs rounded total bg-slate-50"
        placeholder="Total"
        readonly>

</div>
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
function autoResize(el) {
    console.log('autoResize');
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
}
</script>