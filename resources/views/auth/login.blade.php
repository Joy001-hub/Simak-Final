<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIMAK</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        /* App background simulation */
        .app-bg {
            background-color: #f8fafc;
            background-image: radial-gradient(circle at 1px 1px, rgba(148, 163, 184, 0.35) 1px, transparent 0);
            background-size: 22px 22px;
        }

        /* Overlay */
        .overlay {
            background: rgba(248, 250, 252, 0.65);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
        }

        /* Modal animation */
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

        .overlay-enter {
            animation: overlayEnter 0.3s ease-out forwards;
        }

        @keyframes overlayEnter {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
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

        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .input-field {
            transition: all 0.2s ease;
        }

        .input-field:focus {
            border-color: #b91c3b;
            box-shadow: 0 0 0 3px rgba(185, 28, 59, 0.15);
        }

        .float-help {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 200;
            transition: all 0.3s ease;
        }

        .float-help:hover {
            transform: scale(1.1);
        }

        .spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Custom checkbox */
        .custom-checkbox {
            position: relative;
            display: flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }

        .custom-checkbox input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .checkmark {
            height: 20px;
            width: 20px;
            background-color: #f1f5f9;
            border: 2px solid #cbd5e1;
            border-radius: 6px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-checkbox:hover input~.checkmark {
            border-color: #b91c3b;
        }

        .custom-checkbox input:checked~.checkmark {
            background-color: #b91c3b;
            border-color: #b91c3b;
        }

        .checkmark svg {
            display: none;
        }

        .custom-checkbox input:checked~.checkmark svg {
            display: block;
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-flex;
        }

        .tooltip .tooltip-text {
            visibility: hidden;
            opacity: 0;
            width: 220px;
            background-color: #1e293b;
            color: #fff;
            text-align: left;
            border-radius: 8px;
            padding: 10px 12px;
            position: absolute;
            z-index: 100;
            bottom: 130%;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            line-height: 1.4;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: opacity 0.2s, visibility 0.2s;
        }

        .tooltip .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #1e293b transparent transparent transparent;
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        /* Session indicator */
        .session-indicator {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 200;
        }
    </style>
</head>

<body class="min-h-screen app-bg">

    {{-- Session saved indicator --}}
    @if(session('remember_session'))
        <div class="session-indicator tooltip">
            <div class="bg-emerald-500/20 text-emerald-300 px-3 py-2 rounded-full flex items-center gap-2 text-sm">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                        clip-rule="evenodd" />
                </svg>
                <span>Sesi aktif</span>
            </div>
            <span class="tooltip-text">Login otomatis aktif. Sesi akan diingat untuk pembukaan berikutnya.</span>
        </div>
    @endif

    {{-- Overlay --}}
    <div class="fixed inset-0 overlay overlay-enter z-40"></div>

    {{-- Modal Container --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="w-full max-w-md modal-enter">
            <div class="bg-white rounded-3xl card-shadow overflow-hidden">
                {{-- Header with Logo --}}
                <div class="px-8 pt-10 pb-6 text-center">
                    <img class="mx-auto mb-6" src="{{ asset('/logo-app.png') }}" alt="SIMAK Logo"
                        onerror="this.onerror=null;this.src='{{ asset('/logo-simak.svg') }}';"
                        style="width: 160px; height: auto;">
                    <h1 class="text-2xl font-bold text-slate-900 mb-2">Login</h1>
                    <p class="text-slate-500 text-sm">Masuk untuk mengelola akun dan subscription Anda.</p>
                </div>

                {{-- Alert Messages --}}
                @if (session('success'))
                    <div class="mx-6 mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl p-4 text-sm fade-in"
                        role="alert">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mx-6 mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm fade-in"
                        role="alert">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    </div>
                @endif

                {{-- Form --}}
            <form id="login-form" action="{{ route('auth.login') }}" method="POST" class="px-8 pb-8 space-y-5">
                @csrf
                <input type="hidden" name="device_id" id="device_id">
                <input type="hidden" name="device_name" id="device_name">

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2" for="user_id">User ID</label>
                        <input type="number" name="user_id" id="user_id" required inputmode="numeric" autofocus
                            class="input-field w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none"
                            placeholder="Masukkan User ID" value="{{ old('user_id') }}">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2" for="product_id">Product ID</label>
                        <input type="number" name="product_id" id="product_id" required inputmode="numeric"
                            class="input-field w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none"
                            placeholder="Masukkan Product ID" value="{{ old('product_id') }}">
                        <p class="mt-2 text-xs text-slate-400">Gunakan User ID &amp; Product ID dari Sejoli External API.</p>
                    </div>

                    {{-- Remember Session Checkbox --}}
                    <div class="flex items-center gap-2">
                        <label class="custom-checkbox">
                            <input type="checkbox" name="remember" id="remember" value="1">
                            <span class="checkmark">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                        </label>
                        <span class="text-sm text-slate-600">Ingat sesi</span>
                        <div class="tooltip">
                            <svg class="w-4 h-4 text-slate-400 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="tooltip-text">
                                <strong>Dicentang:</strong> Sesi diingat, login otomatis pada pembukaan berikutnya.<br><br>
                                <strong>Tidak dicentang:</strong> Sesi tidak disimpan dan akan diminta aktivasi/login ulang.
                            </span>
                        </div>
                    </div>

                    <button type="submit" id="submit-btn"
                        class="btn-primary w-full text-white font-semibold py-4 rounded-xl flex items-center justify-center gap-2">
                        <span id="btn-text">Login</span>
                        <svg id="btn-spinner" class="w-5 h-5 spinner hidden" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </button>

                    <div class="text-center mt-6 space-y-3">
                        <p class="text-sm text-slate-500">
                            Mengalami masalah subscription? <a href="{{ route('reset') }}"
                                class="text-[#b91c3b] font-semibold hover:underline">Reset Aktivasi</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Floating Help Button --}}
    <a href="https://kavling.pro/member-area/support/" target="_blank"
        class="float-help bg-[#b91c3b] text-white p-4 rounded-full shadow-lg hover:shadow-xl" title="Butuh bantuan?">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
    </a>

    <script>
        // Track page load time to detect stale sessions
        const pageLoadTime = Date.now();
        const SESSION_TIMEOUT_MS = 60 * 60 * 1000; // 1 hour
        let isSubmitting = false;
        const DEVICE_ID_KEY = 'simak_device_id';

        function getOrCreateDeviceId() {
            let deviceId = localStorage.getItem(DEVICE_ID_KEY);
            if (!deviceId) {
                if (crypto?.randomUUID) {
                    deviceId = crypto.randomUUID();
                } else {
                    deviceId = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
                        const r = (Math.random() * 16) | 0;
                        const v = c === 'x' ? r : (r & 0x3) | 0x8;
                        return v.toString(16);
                    });
                }
                localStorage.setItem(DEVICE_ID_KEY, deviceId);
            }
            return deviceId;
        }

        async function resolveDeviceId() {
            if (window.desktop?.license?.getDeviceId) {
                try {
                    return await window.desktop.license.getDeviceId();
                } catch {
                    return getOrCreateDeviceId();
                }
            }
            return getOrCreateDeviceId();
        }

        async function resolveDeviceName() {
            const isDesktop = !!window.desktop?.isElectron;
            let label = isDesktop ? 'SIMAK Desktop' : 'SIMAK Web';
            try {
                if (window.desktop?.runtimeInfo) {
                    const info = await window.desktop.runtimeInfo();
                    if (info?.appVersion) {
                        label += ` v${info.appVersion}`;
                    }
                }
            } catch {
                // Best-effort only.
            }
            const platform = navigator.platform || 'unknown';
            return `${label} (${platform})`;
        }

        function setDeviceFields(deviceId, deviceName) {
            const deviceIdInput = document.getElementById('device_id');
            const deviceNameInput = document.getElementById('device_name');
            if (deviceIdInput && deviceId) {
                deviceIdInput.value = deviceId;
            }
            if (deviceNameInput && deviceName) {
                deviceNameInput.value = deviceName;
            }
            if (deviceId) {
                document.cookie = `simak_device_id=${deviceId}; path=/; max-age=31536000; SameSite=Lax`;
            }
            if (deviceName) {
                document.cookie = `simak_device_name=${encodeURIComponent(deviceName)}; path=/; max-age=31536000; SameSite=Lax`;
            }
        }

        // Reset button state
        function resetButtonState() {
            const btn = document.getElementById('submit-btn');
            const btnText = document.getElementById('btn-text');
            const btnSpinner = document.getElementById('btn-spinner');

            btn.disabled = false;
            btnText.textContent = 'Login';
            btnSpinner.classList.add('hidden');
            isSubmitting = false;
        }

        // Show inline error message
        function showError(message) {
            let existingAlert = document.querySelector('.js-error-alert');
            if (existingAlert) {
                existingAlert.querySelector('span').textContent = message;
                return;
            }

            const alertHtml = `
                <div class="js-error-alert mx-6 mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm fade-in" role="alert">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span>${message}</span>
                    </div>
                </div>
            `;

            const form = document.getElementById('login-form');
            form.insertAdjacentHTML('beforebegin', alertHtml);
        }

        // Check if session might be stale
        function isSessionStale() {
            return (Date.now() - pageLoadTime) > SESSION_TIMEOUT_MS;
        }

        // Refresh CSRF token
        async function refreshCsrfToken() {
            try {
                const response = await fetch(window.location.href, {
                    method: 'GET',
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newToken = doc.querySelector('input[name="_token"]')?.value;

                    if (newToken) {
                        document.querySelector('input[name="_token"]').value = newToken;
                        return true;
                    }
                }
            } catch (e) {
                console.warn('[CSRF] Failed to refresh:', e);
            }
            return false;
        }

        document.getElementById('login-form').addEventListener('submit', async function (e) {
            if (isSubmitting) {
                e.preventDefault();
                return;
            }

            const btn = document.getElementById('submit-btn');
            const btnText = document.getElementById('btn-text');
            const btnSpinner = document.getElementById('btn-spinner');

            // Refresh CSRF if session is stale
            if (isSessionStale()) {
                e.preventDefault();
                btn.disabled = true;
                btnText.textContent = 'Memperbarui sesi...';
                btnSpinner.classList.remove('hidden');

                const refreshed = await refreshCsrfToken();
                if (refreshed) {
                    isSubmitting = true;
                    btnText.textContent = 'Memproses...';
                    this.submit();
                } else {
                    showError('Sesi berakhir, halaman akan dimuat ulang...');
                    setTimeout(() => location.reload(), 1500);
                }
                return;
            }

            isSubmitting = true;
            btn.disabled = true;
            btnText.textContent = 'Memproses...';
            btnSpinner.classList.remove('hidden');

            // Timeout handler
            setTimeout(() => {
                if (isSubmitting) {
                    showError('Permintaan timeout. Silakan coba lagi.');
                    resetButtonState();
                }
            }, 45000);
        });

        // Auto-focus first field
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('user_id').focus();

            // Auto-hide alerts
            const alerts = document.querySelectorAll('[role="alert"]');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });

            Promise.all([resolveDeviceId(), resolveDeviceName()])
                .then(([deviceId, deviceName]) => setDeviceFields(deviceId, deviceName))
                .catch(() => setDeviceFields(getOrCreateDeviceId(), 'SIMAK Web'));
        });

        // Reset form state when navigating back
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                resetButtonState();
            }
        });
    </script>
</body>

</html>
