<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $code ?? 'Error' }} - SIMAK</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .app-bg {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23334155' fill-opacity='0.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .card-shadow {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #b91c3b 0%, #991b32 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #a01835 0%, #831629 100%);
            transform: translateY(-1px);
            box-shadow: 0 10px 25px -5px rgba(185, 28, 59, 0.4);
        }

        .modal-enter {
            animation: modalEnter 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes modalEnter {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .pulse-icon {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }
    </style>
</head>

<body class="min-h-screen app-bg flex items-center justify-center p-4">

    <div class="w-full max-w-md modal-enter">
        <div class="bg-white rounded-3xl card-shadow overflow-hidden">
            {{-- Header --}}
            <div class="px-8 pt-10 pb-6 text-center">
                {{-- Error Icon --}}
                <div class="mx-auto mb-6 w-20 h-20 bg-red-100 rounded-full flex items-center justify-center pulse-icon">
                    <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-slate-900 mb-2">
                    @if (($code ?? 500) == 419)
                        Sesi Berakhir
                    @elseif(($code ?? 500) == 503)
                        Sedang Maintenance
                    @elseif(($code ?? 500) == 404)
                        Halaman Tidak Ditemukan
                    @else
                        Oops! Terjadi Kesalahan
                    @endif
                </h1>

                <p class="text-slate-500 text-sm mb-4">
                    {{ $message ?? 'Terjadi kesalahan sistem.' }}
                </p>

                @if (!empty($description))
                    <p class="text-slate-400 text-xs bg-slate-50 rounded-lg p-3 break-words">
                        {{ $description }}
                    </p>
                @endif
            </div>

            {{-- Actions --}}
            <div class="px-8 pb-8 space-y-3">
                <button onclick="location.reload()"
                    class="btn-primary w-full text-white font-semibold py-4 rounded-xl flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Muat Ulang Halaman
                </button>

                <button onclick="history.back()"
                    class="w-full text-slate-600 font-medium py-3 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali
                </button>

                <a href="{{ route('license.activate.form') }}"
                    class="block w-full text-center text-slate-400 text-sm py-2 hover:text-slate-600 transition-colors">
                    Ke Halaman Utama
                </a>
            </div>

            {{-- Error Code Badge --}}
            @if (!empty($code))
                <div class="text-center pb-6">
                    <span class="inline-block bg-slate-100 text-slate-500 text-xs font-medium px-3 py-1 rounded-full">
                        Error {{ $code }}
                    </span>
                </div>
            @endif
        </div>

        {{-- Help Text --}}
        <p class="text-center text-slate-400 text-xs mt-6">
            Jika masalah berlanjut, hubungi
            <a href="https://kavling.pro/member-area/support/" target="_blank"
                class="text-white hover:underline">support</a>
        </p>
    </div>

</body>

</html>
