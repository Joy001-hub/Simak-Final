<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <x-vite-assets />
</head>

<body
    class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex items-center justify-center p-4 font-sans">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>

    <div class="relative z-10 w-full max-w-md">
        <div class="bg-white/95 backdrop-blur rounded-2xl shadow-2xl border border-white/30 overflow-hidden">
            {{-- Header --}}
            <div class="px-8 pt-8 pb-6 text-center border-b border-slate-200">
                <img class="app-logo-img mx-auto mb-4" src="{{ asset('/logo-app.png') }}" alt="SIMAK Logo"
                    onerror="this.onerror=null;this.src='{{ asset('/logo-simak.svg') }}';"
                    style="width:120px; height:auto;">
                <h1 class="text-2xl font-bold text-slate-900">Login</h1>
                <p class="text-sm text-slate-600 mt-2">Masukkan User ID &amp; Product ID Anda untuk melanjutkan.</p>
            </div>

            @if (session('success'))
                <div class="mx-6 mt-4 bg-green-50 border border-green-200 text-green-800 rounded-lg p-3 text-sm"
                    role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mx-6 mt-4 bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Form --}}
            <form action="{{ route('auth.login') }}" method="POST" class="px-8 py-8 space-y-5">
                @csrf
                <input type="hidden" name="device_id" id="device_id">
                <input type="hidden" name="device_name" id="device_name">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2" for="user_id">User ID</label>
                    <input type="number" name="user_id" id="user_id" required inputmode="numeric" autofocus
                        class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"
                        placeholder="Masukkan User ID" value="{{ old('user_id') }}">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2" for="product_id">Product ID</label>
                    <input type="number" name="product_id" id="product_id" required inputmode="numeric"
                        class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"
                        placeholder="Masukkan Product ID" value="{{ old('product_id') }}">
                    <p class="mt-2 text-xs text-slate-500">Gunakan User ID &amp; Product ID dari Sejoli External API.
                    </p>
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
                    <a href="{{ route('license.activate.form') }}"
                        class="font-semibold text-[#b91c3b] hover:text-[#a01835] underline">
                        aktivasi
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
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

        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('[role="alert"]');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.3s ease-out';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });

            Promise.all([resolveDeviceId(), resolveDeviceName()])
                .then(([deviceId, deviceName]) => setDeviceFields(deviceId, deviceName))
                .catch(() => setDeviceFields(getOrCreateDeviceId(), 'SIMAK Web'));
        });
    </script>
</body>

</html>