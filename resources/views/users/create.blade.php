@extends('layouts.app')

@section('title', 'Tambah User')

@section('content')

<div class="p-6">

    <div class="mb-6">

        <h1 class="text-2xl font-bold text-slate-800">
            Tambah User
        </h1>

        <p class="text-slate-500 text-sm">
            Tambahkan pengguna baru ke sistem SiAdit
        </p>

    </div>

    <div class="bg-white rounded-xl shadow-sm border p-6 max-w-3xl">

        <form method="POST"
              action="{{ route('users.store') }}">

            @csrf

            <div class="grid gap-5">

                {{-- Nama --}}
                <div>

                    <label class="block mb-2 text-sm font-medium text-slate-700">
                        Nama Lengkap
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                        required>

                </div>

                {{-- Email --}}
                <div>

                    <label class="block mb-2 text-sm font-medium text-slate-700">
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                        required>

                </div>

                {{-- Password --}}
                <div>

                    <label class="block mb-2 text-sm font-medium text-slate-700">
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                        required>

                </div>

                {{-- Role --}}
                <div>

                    <label class="block mb-2 text-sm font-medium text-slate-700">
                        Role
                    </label>

                    <select
                        name="role"
                        id="role"
                        class="w-full border rounded-lg px-4 py-2">

                        <option value="admin">
                            Admin
                        </option>

                        <option value="kepala_tpu">
                            Kepala TPU
                        </option>

                        <option value="kepala_uptd">
                            Kepala UPTD
                        </option>

                    </select>

                </div>

                {{-- TPU --}}
                <div id="tpu-section">

                    <label class="block mb-2 text-sm font-medium text-slate-700">
                        TPU
                    </label>

                    <select
                        name="tpu_id"
                        class="w-full border rounded-lg px-4 py-2">

                        <option value="">
                            Pilih TPU
                        </option>

                        @foreach($tpus as $tpu)

                            <option value="{{ $tpu->id }}">

                                {{ $tpu->nama }}

                            </option>

                        @endforeach

                    </select>

                </div>

                {{-- Tombol --}}
                <div class="flex gap-3 pt-2">

                    <a href="{{ route('users.index') }}"
                        class="px-5 py-2 rounded-lg border">

                        Batal

                    </a>

                    <button
                        type="submit"
                        class="bg-[#1A3A5C] hover:bg-[#234a73] text-white px-5 py-2 rounded-lg">

                        Simpan User

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const role = document.getElementById('role');

    const tpuSection = document.getElementById('tpu-section');

    function toggleTPU()
    {
        if(role.value === 'kepala_tpu')
        {
            tpuSection.style.display = 'block';
        }
        else
        {
            tpuSection.style.display = 'none';
        }
    }

    toggleTPU();

    role.addEventListener('change', toggleTPU);

});

</script>

@endsection