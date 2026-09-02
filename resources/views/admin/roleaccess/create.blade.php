<div x-data="{ open: false }">

    <!-- Button -->
    <button
        type="button"
        @click="open = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">
        Add New User
    </button>

    <!-- Modal -->
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">

        <div
            @click.away="open = false"
            class="bg-white w-full max-w-lg rounded-2xl p-6 shadow-xl">

            <h2 class="text-lg font-bold mb-4">
                Add New User
            </h2>

            <form method="POST" action="{{ route('users.store') }}">
                @csrf

                <div class="space-y-3">

                    <!-- Full Name -->
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Full Name"
                        required
                        class="w-full border rounded-lg p-2 text-sm">

                    <!-- Email -->
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Email"
                        required
                        class="w-full border rounded-lg p-2 text-sm">

                    <!-- Employee ID -->
                    <input
                        type="text"
                        name="employee_id"
                        value="{{ old('employee_id') }}"
                        placeholder="Employee ID"
                        required
                        class="w-full border rounded-lg p-2 text-sm">

                    <!-- Department -->
                    <input
                        type="text"
                        name="department"
                        value="{{ old('department') }}"
                        placeholder="Department"
                        required
                        class="w-full border rounded-lg p-2 text-sm">


                        <!-- Companies -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Companies
                            </label>

                            <div class="border border-slate-200 rounded-lg p-3 max-h-40 overflow-y-auto space-y-2 bg-white">

                                @forelse($companies as $company)

                                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 cursor-pointer">

                                        <input
                                            type="checkbox"
                                            name="company_ids[]"
                                            value="{{ $company->company_id }}"
                                            class="company-checkbox w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        >

                                        <span class="text-sm text-slate-700">
                                            {{ $company->name }}

                                            @if($company->code)
                                                <span class="text-xs text-slate-400">
                                                    ({{ $company->code }})
                                                </span>
                                            @endif
                                        </span>

                                    </label>

                                @empty

                                    <p class="text-sm text-slate-400">
                                        No active companies available.
                                    </p>

                                @endforelse

                            </div>

                            @error('company_ids')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>
                            @enderror

                            @error('company_ids.*')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>


                    <!-- Role -->
                    <select
                        name="roles[]"
                        required
                        class="w-full border rounded-lg p-2 text-sm">

                        <option value="">
                            Select Role
                        </option>

                        @foreach($roles as $role)

                            <option
                                value="{{ $role->name }}"
                                @selected(in_array(
                                    $role->name,
                                    old('roles', [])
                                ))>

                                {{ $role->name }}

                            </option>

                        @endforeach

                    </select>


                    <!-- Password -->
                    <input
                        type="password"
                        name="password"
                        placeholder="Password"
                        minlength="8"
                        required
                        class="w-full border rounded-lg p-2 text-sm">

                    <!-- Confirm Password -->
                    <input
                        type="password"
                        name="password_confirmation"
                        placeholder="Confirm Password"
                        minlength="8"
                        required
                        class="w-full border rounded-lg p-2 text-sm">

                </div>


                <!-- Buttons -->
                <div class="flex justify-end gap-2 mt-5">

                    <button
                        type="button"
                        @click="open = false"
                        class="px-4 py-2 text-sm border rounded-lg">

                        Cancel

                    </button>

                    <button
                        type="submit"
                        class="px-4 py-2 text-sm bg-blue-600
                               text-white rounded-lg">

                        Save

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>