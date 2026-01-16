<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - SIMAK</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <x-vite-assets />
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .app-bg {
            background-color: #f8fafc;
            background-image: radial-gradient(circle at 1px 1px, rgba(148, 163, 184, 0.35) 1px, transparent 0);
            background-size: 22px 22px;
        }

        .overlay {
            background: rgba(248, 250, 252, 0.65);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
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

        .input-field {
            transition: all 0.2s ease;
        }

        .input-field:focus {
            border-color: #b91c3b;
            box-shadow: 0 0 0 3px rgba(185, 28, 59, 0.15);
        }
    </style>
</head>

<body class="min-h-screen app-bg">
    <div class="fixed inset-0 overlay z-40"></div>

    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="w-full max-w-md modal-enter">
            <div class="bg-white rounded-3xl card-shadow overflow-hidden">
                <div class="px-8 pt-10 pb-6 text-center">
                    <img class="mx-auto mb-6" src="/logo-app.png" alt="SIMAK Logo"
                        onerror="this.onerror=null;this.src='/logo-simak.svg';"
                        style="width: 160px; height: auto;">
                    <h1 class="text-2xl font-bold text-slate-900 mb-2">Lupa Password</h1>
                    <p class="text-slate-500 text-sm">Masukkan email terdaftar. OTP dikirim ke WhatsApp.</p>
                </div>

                @if (session('success'))
                    <div class="mx-6 mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl p-4 text-sm"
                        role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mx-6 mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('password.forgot.send') }}" method="POST" class="px-8 pb-8 space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2" for="email">Email</label>
                        <input type="email" name="email" id="email" required
                            class="input-field w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none"
                            placeholder="contoh@email.com" value="{{ old('email') }}">
                        <p class="mt-2 text-xs text-slate-400">OTP hanya dikirim via WhatsApp.</p>
                    </div>

                    <button type="submit"
                        class="btn-primary w-full text-white font-semibold py-4 rounded-xl flex items-center justify-center">
                        Kirim OTP
                    </button>

                    <p class="text-center text-sm text-slate-500 mt-4">
                        Sudah ingat password? <a href="{{ route('login') }}"
                            class="text-[#b91c3b] font-semibold hover:underline">Login</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
