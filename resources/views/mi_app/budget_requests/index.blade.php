<x-mi_app>
    <div class="max-w-5xl mx-auto py-6">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-xl font-semibold">Budget Requests</h1>
            <a href="{{ route('budget_requests.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded text-sm">New Request</a>
        </div>

        <table class="w-full text-sm border">
            <thead>
                <tr class="bg-gray-100">
                    <th class="p-2 border text-left">Control ID</th>
                    <th class="p-2 border text-left">Employee</th>
                    <th class="p-2 border text-left">Department</th>
                    <th class="p-2 border text-right">Budget Total</th>
                    <th class="p-2 border text-left">Status</th>
                    <th class="p-2 border"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($budgetRequests as $br)
                    <tr>
                        <td class="border p-2">{{ $br->control_id }}</td>
                        <td class="border p-2">{{ $br->employee->name }}</td>
                        <td class="border p-2">{{ $br->department }}</td>
                        <td class="border p-2 text-right">{{ number_format($br->budget_total, 2) }}</td>
                        <td class="border p-2">{{ str_replace('_', ' ', ucfirst($br->status)) }}</td>
                        <td class="border p-2"><a href="{{ route('budget_requests.show', $br) }}" class="text-blue-600 underline">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">{{ $budgetRequests->links() }}</div>
    </div>
</x-mi_app>
