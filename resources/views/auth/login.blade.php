<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - SiAdit</title>

    {{-- CSS hasil kompilasi Vite (konsisten dengan layout aplikasi) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-100 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-md">

        <div class="text-center mb-8">

            <div
                class="w-16 h-16 bg-[#1A3A5C] rounded-xl flex items-center justify-center mx-auto mb-4">

                <svg width="30" height="30" viewBox="0 0 24 24"
                    fill="none" stroke="white"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round">

                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />

                </svg>
            </div>

            <h1 class="text-3xl font-bold text-[#1A3A5C]">
                SiAdit
            </h1>

            <p class="text-slate-500 mt-2">
                Sistem Audit Sinkronisasi Data Makam
            </p>

        </div>

        <div class="bg-white rounded-2xl shadow-lg p-8">

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium">
                        Email
                    </label>

                    <input type="email"
                        name="email"
                        required
                        autofocus
                        class="w-full border rounded-lg px-4 py-3">
                </div>

                <div class="mb-6">
                    <label class="block mb-2 text-sm font-medium">
                        Password
                    </label>

                    <input type="password"
                        name="password"
                        required
                        class="w-full border rounded-lg px-4 py-3">
                </div>

                <button type="submit"
                    class="w-full bg-[#1A3A5C] text-white py-3 rounded-lg font-semibold hover:opacity-90">

                    Login

                </button>

                <div class="text-center mt-5 text-xs text-slate-400">

                    Butuh akun? Hubungi Admin untuk pembuatan akun
                    melalui menu <span class="font-semibold text-slate-600">Kelola User</span>.

                </div>

            </form>

        </div>

    </div>

</body>

</html>