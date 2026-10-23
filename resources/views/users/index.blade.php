@extends('layouts.app')

@section('title', 'Kelola User')

@section('content')

<div class="p-6">

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">

        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                Kelola User
            </h1>

            <p class="text-slate-500 text-sm">
                Manajemen pengguna sistem SiAdit
            </p>
        </div>

        <a href="{{ route('users.create') }}"
            class="bg-[#1A3A5C] hover:bg-[#234a73] text-white px-4 py-2 rounded-lg">

            + Tambah User

        </a>

    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">

        <table class="w-full">

            <thead class="bg-slate-50">

                <tr>

                    <th class="text-left p-4">Nama</th>
                    <th class="text-left p-4">Email</th>
                    <th class="text-left p-4">Role</th>
                    <th class="text-left p-4">TPU</th>
                    <th class="text-center p-4">Aksi</th>

                </tr>

            </thead>

            <tbody>

                @forelse($users as $user)

                    <tr class="border-t">

                        <td class="p-4">
                            {{ $user->name }}
                        </td>

                        <td class="p-4">
                            {{ $user->email }}
                        </td>

                        <td class="p-4">

                            @if($user->role == 'admin')

                                <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-medium">
                                    Admin
                                </span>

                            @elseif($user->role == 'kepala_tpu')

                                <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-medium">
                                    Kepala TPU
                                </span>

                            @elseif($user->role == 'kepala_uptd')

                                <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium">
                                    Kepala UPTD
                                </span>

                            @endif

                        </td>

                        <td class="p-4">

                            {{ $user->tpu?->nama ?? '-' }}

                        </td>

                        <td class="p-4">

                            <div class="flex justify-center gap-2">

                                <a href="{{ route('users.edit', $user->id) }}"
                                    class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded">

                                    Edit

                                </a>

                                @if($user->id != auth()->id())

                                    <form method="POST"
                                        action="{{ route('users.destroy', $user->id) }}">

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            onclick="return confirm('Hapus user ini?')"
                                            class="px-3 py-1 bg-red-100 text-red-700 rounded">

                                            Hapus

                                        </button>

                                    </form>

                                @endif

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="5"
                            class="text-center py-8 text-slate-500">

                            Belum ada data user

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    <div class="mt-5">

        {{ $users->links() }}

    </div>

</div>

@endsection