<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex items-center justify-center p-4 font-sans">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>

    <div class="relative z-10 w-full max-w-md">
        <div class="bg-white/95 backdrop-blur rounded-2xl shadow-2xl border border-white/30 overflow-hidden">
            {{-- Header --}}
            <div class="px-8 pt-8 pb-6 text-center border-b border-slate-200">
                <img class="app-logo-img mx-auto mb-4"
                     src="{{ asset('/logo-app.png') }}"
                     alt="SIMAK Logo"
                     onerror="this.onerror=null;this.src='{{ asset('/logo-simak.svg') }}';"
                     style="width:120px; height:auto;">
                <h1 class="text-2xl font-bold text-slate-900">Login</h1>
                <p class="text-sm text-slate-600 mt-2">Masukkan license key Anda untuk melanjutkan.</p>
            </div>

            @if (session('success'))
                <div class="mx-6 mt-4 bg-green-50 border border-green-200 text-green-800 rounded-lg p-3 text-sm" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mx-6 mt-4 bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Form --}}
            <form action="{{ route('license.login') }}" method="POST" class="px-8 py-8 space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2" for="license">License Key</label>
                    <input type="text" name="license" id="license" required autocomplete="off"
                        class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition uppercase"
                        placeholder="LISENSI-XXXX-XXXX" value="{{ old('license') }}">
                </div>

                <button type="submit"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-lg transition duration-200 shadow-md hover:shadow-lg">
                    Login
                </button>
            </form>

            {{-- Footer Link --}}
            <div class="px-8 pb-8 text-center">
                <p class="text-sm text-slate-600">
                    Belum aktivasi?
                    <a href="{{ route('license.activate.form') }}" class="font-semibold text-[#b91c3b] hover:text-[#a01835] underline">
                        aktivasi
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('[role="alert"]');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.3s ease-out';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>
