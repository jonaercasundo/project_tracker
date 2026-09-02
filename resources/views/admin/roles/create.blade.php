<x-app-layout>

    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Role
            </h2>

            <p class="text-sm text-gray-500 mt-1">
                Create a new role that can be assigned to users.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Validation Errors --}}
            @if($errors->any())
                <div class="mb-6 rounded-lg bg-red-50 border border-red-200
                            px-4 py-3 text-red-700">

                    <div class="font-semibold mb-2">
                        Please correct the following errors:
                    </div>

                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                </div>
            @endif

            <div class="bg-white shadow-sm rounded-xl border border-gray-200">

                {{-- Header --}}
                <div class="px-6 py-5 border-b border-gray-200">
                    <div class="flex items-center gap-3">

                        <div class="w-10 h-10 rounded-lg bg-emerald-100
                                    flex items-center justify-center">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                 class="w-5 h-5 text-emerald-600"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor">

                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M9 12l2 2 4-4m5.618-4.016A11.955
                                         11.955 0 0112 2.944a11.955 11.955
                                         0 01-8.618 3.04A12.02 12.02 0
                                         015 9c0 5.591 3.824 10.29 9
                                         11.622C18.176 19.29 22 14.591
                                         22 9c0-1.042-.133-2.052-.382-3.016z" />

                            </svg>

                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">
                                New Role
                            </h3>

                            <p class="text-sm text-gray-500">
                                Enter the name of the new role.
                            </p>
                        </div>

                    </div>
                </div>

                {{-- Form --}}
                <form method="POST"
                      action="{{ route('roles.store') }}">

                    @csrf

                    <div class="p-6">

                        {{-- Role Name --}}
                        <div>
                            <label for="name"
                                   class="block text-sm font-medium text-gray-700 mb-2">
                                Role Name
                            </label>

                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name') }}"
                                placeholder="e.g. Manager"
                                required
                                autofocus
                                class="w-full rounded-xl border-gray-300
                                       focus:border-emerald-500
                                       focus:ring-emerald-500"
                            >

                            @error('name')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                            <p class="mt-2 text-xs text-gray-500">
                                Use a clear name such as Administrator,
                                Manager, Finance, Warehouse Officer, or Staff.
                            </p>
                        </div>

                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200
                                rounded-b-xl flex items-center justify-end gap-3">

                        <a href="{{ route('roles.index') }}"
                           class="inline-flex items-center px-4 py-2
                                  border border-gray-300 rounded-lg
                                  text-sm font-medium text-gray-700
                                  bg-white hover:bg-gray-50">

                            Cancel

                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center px-5 py-2
                                   bg-emerald-600 border border-transparent
                                   rounded-lg text-sm font-semibold text-white
                                   hover:bg-emerald-700
                                   focus:outline-none
                                   focus:ring-2 focus:ring-emerald-500
                                   focus:ring-offset-2">

                            Create Role

                        </button>

                    </div>

                </form>

            </div>

        </div>
    </div>

</x-app-layout>