<x-finance_app-layout>
<div class="max-w-3xl mx-auto space-y-4">

    <h1 class="text-xl font-bold">Add PPL Form</h1>

    <form method="POST" action="/ppl-forms/store" class="space-y-3">
        @csrf

        <input type="text" name="project_code" class="form-control" placeholder="Project Code">
        <input type="text" name="lot_number" class="form-control" placeholder="Lot #">
        <input type="text" name="project_title" class="form-control" placeholder="Project Title">
        <input type="text" name="project_id_no" class="form-control" placeholder="Project ID No">
        <input type="text" name="region" class="form-control" placeholder="Region">

        <input type="date" name="bid_opening" class="form-control">

        <input type="number" step="0.01" name="abc" class="form-control" placeholder="ABC">

        <input type="text" name="bidder" class="form-control" placeholder="Bidder">
        <input type="text" name="authorized_signatory" class="form-control" placeholder="Authorized Signatory">

        <button class="btn btn-primary">Save</button>
    </form>

</div>
</x-finance_app-layout>