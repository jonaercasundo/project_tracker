<x-app-layout>
<div class="container py-4">

    <h3 class="mb-4">Role Access Management</h3>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Department</th>
                <th>Role</th>
                <th width="180">Action</th>
            </tr>
        </thead>

        <tbody>
            @foreach($users as $user)
            <tr>

                <td>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-slate-200 rounded-full flex items-center justify-center font-bold">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>

                        <div>
                            <div class="font-semibold">{{ $user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $user->email }}</div>
                        </div>
                    </div>
                </td>

                <td>{{ $user->department ?? 'N/A' }}</td>

                <td>
                    <span class="text-xs font-bold uppercase">
                        {{ $user->role }}
                    </span>
                </td>

                <td class="text-right">

                    <button class="btn btn-sm btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#editRoleModal{{ $user->id }}">
                        Edit
                    </button>

                    <button class="btn btn-sm btn-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteRoleModal{{ $user->id }}">
                        Delete
                    </button>

                </td>

            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- ================= MODALS OUTSIDE TABLE ================= --}}

@foreach($users as $user)

<!-- EDIT MODAL -->
<div class="modal fade" id="editRoleModal{{ $user->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <form method="POST" action="{{ route('roleaccess.update') }}">
                @csrf
                @method('PATCH')

                <input type="hidden" name="user_id" value="{{ $user->id }}">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" class="form-control" value="{{ $user->name }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label>Role</label>
                        <select name="role" class="form-control">
                            <option value="Administrator" @selected($user->role == 'Administrator')>Administrator</option>
                            <option value="Admin" @selected($user->role == 'Admin')>Admin</option>
                            <option value="Manager" @selected($user->role == 'Manager')>Manager</option>
                            <option value="User" @selected($user->role == 'User')>User</option>
                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="deleteRoleModal{{ $user->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <form method="POST" action="{{ route('roleaccess.destroy') }}">
                @csrf
                @method('DELETE')

                <input type="hidden" name="user_id" value="{{ $user->id }}">

                <div class="modal-header">
                    <h5 class="modal-title text-danger">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    Delete user <b>{{ $user->name }}</b>?
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>

            </form>

        </div>
    </div>
</div>

@endforeach

</x-app-layout>