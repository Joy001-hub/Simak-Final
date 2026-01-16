{{-- Maintenance Modal Popup untuk Popup License Validation --}}
<div id="maintenanceModal" class="maintenance-modal-overlay">
    <div class="maintenance-modal-content">
        <!-- Header dengan icon dan title -->
        <div class="maintenance-modal-header">
            <svg class="maintenance-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Z"/>
                <path d="M12 6v6M12 17h.01"/>
            </svg>
            <h2 class="maintenance-modal-title">Maintenance Rutin</h2>
        </div>

        <!-- Body dengan pesan -->
        <div class="maintenance-modal-body">
            <p class="maintenance-modal-text">Mohon aktifkan internet untuk melanjutkan</p>
            <div class="maintenance-spinner">
                <div class="spinner-ring"></div>
            </div>
        </div>

        <!-- Footer dengan status -->
        <div class="maintenance-modal-footer">
            <span class="maintenance-status">Mencoba koneksi...</span>
        </div>
    </div>
</div>

<style>
    /* Maintenance Modal Styles */
    .maintenance-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(2px);
    }

    .maintenance-modal-overlay.active {
        display: flex;
    }

    .maintenance-modal-content {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        width: min(400px, 90vw);
        padding: 40px 30px;
        text-align: center;
        animation: modalSlideIn 0.3s ease-out;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .maintenance-modal-header {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .maintenance-icon {
        width: 32px;
        height: 32px;
        color: #b91c3b;
        stroke-width: 2;
    }

    .maintenance-modal-title {
        font-size: 20px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
        letter-spacing: -0.5px;
    }

    .maintenance-modal-body {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 24px;
        margin-bottom: 20px;
    }

    .maintenance-modal-text {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
        line-height: 1.5;
    }

    .maintenance-spinner {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 60px;
    }

    .spinner-ring {
        border: 3px solid #f3f4f6;
        border-top: 3px solid #b91c3b;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    .maintenance-modal-footer {
        border-top: 1px solid #e5e7eb;
        padding-top: 16px;
    }

    .maintenance-status {
        font-size: 12px;
        color: #9ca3af;
        font-weight: 500;
    }

    /* Disable user interaction ketika modal aktif */
    body.maintenance-modal-active {
        overflow: hidden;
    }

    .maintenance-modal-overlay.active ~ * {
        pointer-events: none;
    }
</style>

<script>
    (function() {
        const maintenanceModal = document.getElementById('maintenanceModal');
        const statusText = document.querySelector('.maintenance-status');
        let checkInterval = null;
        let lastCheckTime = Date.now();

        /**
         * Tampilkan maintenance modal
         */
        window.showMaintenanceModal = function() {
            if (!maintenanceModal) return;

            maintenanceModal.classList.add('active');
            document.body.classList.add('maintenance-modal-active');

            // Mulai check internet setiap 5 detik
            if (!checkInterval) {
                checkInterval = setInterval(checkInternetConnection, 5000);
                // Check langsung tanpa menunggu
                checkInternetConnection();
            }
        };

        /**
         * Sembunyikan maintenance modal
         */
        window.hideMaintenanceModal = function() {
            if (!maintenanceModal) return;

            maintenanceModal.classList.remove('active');
            document.body.classList.remove('maintenance-modal-active');

            if (checkInterval) {
                clearInterval(checkInterval);
                checkInterval = null;
            }
        };

        /**
         * Check internet connection dengan simple fetch
         */
        async function checkInternetConnection() {
            try {
                // Update status text
                const now = new Date();
                const timeStr = now.toLocaleTimeString('id-ID');
                statusText.textContent = `Mencoba koneksi... (${timeStr})`;

                // Cek dengan fetch ke halaman simpel (bisa ganti dengan endpoint sendiri)
                const response = await fetch('/', { method: 'HEAD', cache: 'no-cache' });

                if (response.ok) {
                    statusText.textContent = 'Koneksi berhasil!';

                    // Tunggu 1 detik baru close modal
                    setTimeout(() => {
                        hideMaintenanceModal();
                        // Reload halaman atau trigger retry validasi
                        location.reload();
                    }, 1000);
                }
            } catch (error) {
                // Koneksi masih error, terus tunggu
                statusText.textContent = `Menunggu koneksi... (${new Date().toLocaleTimeString('id-ID')})`;
            }
        }

        // Optional: Expose untuk debugging
        window.maintenanceModal = {
            show: window.showMaintenanceModal,
            hide: window.hideMaintenanceModal,
            isActive: () => maintenanceModal?.classList.contains('active') || false
        };
    })();
</script>

