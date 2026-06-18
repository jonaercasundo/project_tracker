<div x-data="{ open: false }">

    <!-- Button -->
    <button
        @click="open = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">
        Add New User
    </button>

    <!-- Modal -->
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">

        <div
            @click.away="open = false"
            class="bg-white w-full max-w-lg rounded-2xl p-6 shadow-xl">

            <h2 class="text-lg font-bold mb-4">Add New User</h2>

            <form method="POST" action="{{ route('users.store') }}">
                @csrf

                <div class="space-y-3">

                    <input type="text" name="name" placeholder="Full Name"
                        class="w-full border rounded-lg p-2 text-sm">

                    <input type="email" name="email" placeholder="Email"
                        class="w-full border rounded-lg p-2 text-sm">

                    <input type="text" name="employee_id" placeholder="Employee ID"
                        class="w-full border rounded-lg p-2 text-sm">

                    <input type="text" name="department" placeholder="Department"
                        class="w-full border rounded-lg p-2 text-sm">

                    <select name="role" class="w-full border rounded-lg p-2 text-sm">
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>

                    <input type="password" name="password" placeholder="Password"
                        class="w-full border rounded-lg p-2 text-sm">

                </div>

                <div class="flex justify-end gap-2 mt-5">
                    <button type="button"
                        @click="open = false"
                        class="px-4 py-2 text-sm border rounded-lg">
                        Cancel
                    </button>

                    <button type="submit"
                        class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg">
                        Save
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>