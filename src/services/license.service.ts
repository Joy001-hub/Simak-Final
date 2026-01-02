import { Injectable, signal, computed, effect } from '@angular/core';
import {
    LicenseActivationRequest,
    LicenseResponse,
    LicenseState,
    SubscriptionStatus,
    LICENSE_ERROR_ACTIONS,
} from '../models/license.model';

// Configuration - Sejoli License Server
const LICENSE_CONFIG = {
    BASE_URL: 'https://kavling.pro', // Sejoli license server
    ENDPOINTS: {
        ACTIVATE: '/sejoli-license/',
        VALIDATE: '/sejoli-validate-license/',
        RESET: '/sejoli-delete-license/',
    },
    STORAGE_KEYS: {
        DEVICE_ID: 'sejoli_device_string',
        LICENSE_KEY: 'sejoli_license_key',
        USER_EMAIL: 'sejoli_user_email',
        LAST_VALIDATED: 'sejoli_last_validated',
        CACHED_STATUS: 'sejoli_cached_status',
    },
    VALIDATION_INTERVAL_MS: 24 * 60 * 60 * 1000, // 24 hours
};

// Declare desktop API from Electron preload
declare global {
    interface Window {
        desktop?: {
            runtimeInfo: () => Promise<{
                isDev: boolean;
                isPackaged: boolean;
                appVersion: string;
                userDataPath: string;
                platform: string;
            }>;
            isElectron: boolean;
            license?: {
                saveToken: (token: string) => Promise<boolean>;
                getToken: () => Promise<string | null>;
                clearToken: () => Promise<boolean>;
                getDeviceId: () => Promise<string>;
                saveLicenseData: (data: Record<string, string>) => Promise<boolean>;
                getLicenseData: () => Promise<Record<string, string> | null>;
                clearLicenseData: () => Promise<boolean>;
            };
        };
    }
}

@Injectable({
    providedIn: 'root',
})
export class LicenseService {
    // License state signals
    private _state = signal<LicenseState>({
        isValid: false,
        isLoading: true,
        isActivated: false,
        errorMessage: null,
        subscriptionStatus: null,
        expirationDate: null,
        productName: null,
        lastValidated: null,
    });

    // Public computed signals
    readonly isLicenseValid = computed(() => this._state().isValid);
    readonly isLoading = computed(() => this._state().isLoading);
    readonly isActivated = computed(() => this._state().isActivated);
    readonly errorMessage = computed(() => this._state().errorMessage);
    readonly subscriptionStatus = computed(() => this._state().subscriptionStatus);
    readonly expirationDate = computed(() => this._state().expirationDate);
    readonly productName = computed(() => this._state().productName);
    readonly state = computed(() => this._state());

    private validationIntervalId: any = null;
    private deviceId: string | null = null;

    constructor() {
        // Initialize license check on service creation
        this.initializeLicense();
    }

    /**
     * Initialize license system - check for existing activation
     */
    async initializeLicense(): Promise<void> {
        this.updateState({ isLoading: true, errorMessage: null });

        try {
            // Check if we have stored license data
            const deviceId = await this.getDeviceId();
            const token = await this.getToken();

            if (deviceId && token) {
                // We have existing activation, validate it
                this.updateState({ isActivated: true });
                await this.validate();
            } else {
                // No activation found
                this.updateState({
                    isValid: false,
                    isActivated: false,
                    isLoading: false,
                });
            }

            // Start background validation
            this.startBackgroundValidation();
        } catch (error) {
            console.error('License initialization error:', error);
            this.updateState({
                isLoading: false,
                errorMessage: 'Gagal menginisialisasi lisensi',
            });
        }
    }

    /**
     * Activate license with user credentials
     */
    async activate(
        email: string,
        password: string,
        licenseKey: string
    ): Promise<{ success: boolean; message: string }> {
        this.updateState({ isLoading: true, errorMessage: null });

        try {
            const deviceId = await this.getOrCreateDeviceId();

            const requestBody: LicenseActivationRequest = {
                user_email: email,
                user_pass: password,
                license: licenseKey,
                string: deviceId,
            };

            // Use form-urlencoded - server doesn't accept JSON!
            const formData = new URLSearchParams();
            formData.append('user_email', email);
            formData.append('user_pass', password);
            formData.append('license', licenseKey);
            formData.append('string', deviceId);

            const response = await fetch(
                `${LICENSE_CONFIG.BASE_URL}${LICENSE_CONFIG.ENDPOINTS.ACTIVATE}`,
                {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString(),
                }
            );

            const data: LicenseResponse = await response.json();

            if (data.valid && data.data?.token) {
                // Save token securely
                await this.saveToken(data.data.token);

                // Save license data
                await this.saveLicenseData({
                    licenseKey,
                    email,
                    productName: data.data.product_name || '',
                    subscriptionStatus: data.data.subscription_status || 'active',
                    expirationDate: data.data.expiration_date || '',
                });

                this.updateState({
                    isValid: true,
                    isActivated: true,
                    isLoading: false,
                    subscriptionStatus: data.data.subscription_status || 'active',
                    expirationDate: data.data.expiration_date || null,
                    productName: data.data.product_name || null,
                    lastValidated: Date.now(),
                    errorMessage: null,
                });

                return { success: true, message: 'Aktivasi berhasil!' };
            } else {
                this.updateState({
                    isLoading: false,
                    errorMessage: data.message,
                });
                return { success: false, message: data.message };
            }
        } catch (error) {
            const errorMsg = 'Gagal terhubung ke server lisensi. Periksa koneksi internet Anda.';
            this.updateState({
                isLoading: false,
                errorMessage: errorMsg,
            });
            return { success: false, message: errorMsg };
        }
    }

    /**
     * Validate current license
     */
    async validate(): Promise<{ valid: boolean; message: string }> {
        try {
            const deviceId = await this.getDeviceId();
            const token = await this.getToken();

            if (!deviceId) {
                this.updateState({
                    isValid: false,
                    isLoading: false,
                    errorMessage: 'Device ID tidak ditemukan',
                });
                return { valid: false, message: 'Device ID tidak ditemukan' };
            }

            const headers: Record<string, string> = {
                'Content-Type': 'application/x-www-form-urlencoded',
            };

            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            // Use form-urlencoded - server doesn't accept JSON!
            const formData = new URLSearchParams();
            formData.append('string', deviceId);

            const response = await fetch(
                `${LICENSE_CONFIG.BASE_URL}${LICENSE_CONFIG.ENDPOINTS.VALIDATE}`,
                {
                    method: 'POST',
                    headers,
                    body: formData.toString(),
                }
            );

            const data: LicenseResponse = await response.json();

            if (data.valid) {
                this.updateState({
                    isValid: true,
                    isLoading: false,
                    subscriptionStatus: (data.data?.subscription_status as SubscriptionStatus) || 'active',
                    expirationDate: data.data?.expiration_date || null,
                    productName: data.data?.product_name || null,
                    lastValidated: Date.now(),
                    errorMessage: null,
                });

                // Cache valid status for offline use
                this.cacheValidStatus(true);

                return { valid: true, message: data.message };
            } else {
                // Check if token expired - need to re-login
                if (
                    data.message.includes('Token') ||
                    data.message.includes('token')
                ) {
                    await this.clearLicenseData();
                    this.updateState({
                        isValid: false,
                        isActivated: false,
                        isLoading: false,
                        errorMessage: 'Sesi berakhir, silakan aktivasi ulang',
                    });
                } else {
                    this.updateState({
                        isValid: false,
                        isLoading: false,
                        subscriptionStatus: (data.data?.subscription_status as SubscriptionStatus) || null,
                        expirationDate: data.data?.expiration_date || null,
                        errorMessage: data.message,
                    });
                }

                this.cacheValidStatus(false);
                return { valid: false, message: data.message };
            }
        } catch (error) {
            // Network error - check cached status for offline mode
            console.warn('Validation failed, checking cached status:', error);
            const cachedValid = this.getCachedStatus();

            if (cachedValid !== null) {
                this.updateState({
                    isValid: cachedValid,
                    isLoading: false,
                    errorMessage: cachedValid ? null : 'Mode offline - status dari cache',
                });
                return {
                    valid: cachedValid,
                    message: 'Mode offline - menggunakan status tersimpan',
                };
            }

            this.updateState({
                isLoading: false,
                errorMessage: 'Gagal validasi lisensi. Periksa koneksi internet.',
            });
            return { valid: false, message: 'Gagal validasi lisensi' };
        }
    }

    /**
     * Reset/delete license from this device
     */
    async reset(): Promise<{ success: boolean; message: string }> {
        this.updateState({ isLoading: true, errorMessage: null });

        try {
            const deviceId = await this.getDeviceId();
            const token = await this.getToken();
            const licenseKey = this.getLicenseKey();

            if (!deviceId || !licenseKey) {
                await this.clearLicenseData();
                this.updateState({
                    isValid: false,
                    isActivated: false,
                    isLoading: false,
                });
                return { success: true, message: 'Data lisensi lokal dihapus' };
            }

            const headers: Record<string, string> = {
                'Content-Type': 'application/x-www-form-urlencoded',
            };

            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            // Use form-urlencoded - server doesn't accept JSON!
            const formData = new URLSearchParams();
            formData.append('license', licenseKey);
            formData.append('string', deviceId);

            const response = await fetch(
                `${LICENSE_CONFIG.BASE_URL}${LICENSE_CONFIG.ENDPOINTS.RESET}`,
                {
                    method: 'POST',
                    headers,
                    body: formData.toString(),
                }
            );

            const data: LicenseResponse = await response.json();

            // FALLBACK: If server returns valid:false, force delete license.json and redirect
            if (!data.valid) {
                console.warn('[License] Server returned valid:false on reset, forcing local license deletion');
                await this.forceRevokeAndRedirect();
                return { success: true, message: data.message || 'Lisensi tidak valid. Mengarahkan ke halaman aktivasi...' };
            }

            // Clear local data regardless of server response
            await this.clearLicenseData();

            this.updateState({
                isValid: false,
                isActivated: false,
                isLoading: false,
                subscriptionStatus: null,
                expirationDate: null,
                productName: null,
                lastValidated: null,
                errorMessage: null,
            });

            return { success: true, message: 'Lisensi berhasil di-reset. Silakan aktivasi di device baru.' };
        } catch (error) {
            // Still clear local data on error
            await this.clearLicenseData();
            this.updateState({
                isValid: false,
                isActivated: false,
                isLoading: false,
            });
            return { success: true, message: 'Data lisensi lokal dihapus (offline)' };
        }
    }

    /**
     * Force revoke license locally and redirect to activation page
     * Called when server returns valid:false on sejoli-delete-license
     */
    async forceRevokeAndRedirect(): Promise<void> {
        try {
            // Call backend API to force delete license.json
            const response = await fetch('/api/license/force-revoke', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
            });

            const result = await response.json();
            console.log('[License] Force revoke result:', result);

            // Clear all local data
            await this.clearLicenseData();

            this.updateState({
                isValid: false,
                isActivated: false,
                isLoading: false,
                subscriptionStatus: null,
                expirationDate: null,
                productName: null,
                lastValidated: null,
                errorMessage: 'Lisensi tidak valid. Silakan aktivasi ulang.',
            });

            // Force redirect to activation page
            if (result.redirect) {
                window.location.href = result.redirect;
            } else {
                // Fallback redirect path
                window.location.href = '/license/activate';
            }
        } catch (error) {
            console.error('[License] Force revoke failed:', error);
            // Still clear local data and redirect
            await this.clearLicenseData();
            this.updateState({
                isValid: false,
                isActivated: false,
                isLoading: false,
            });
            window.location.href = '/license/activate';
        }
    }

    /**
     * Get user-friendly action for error message
     */
    getErrorAction(errorMessage: string): string | null {
        for (const [key, action] of Object.entries(LICENSE_ERROR_ACTIONS)) {
            if (errorMessage.includes(key)) {
                return action;
            }
        }
        return null;
    }

    /**
     * Start background validation interval
     */
    startBackgroundValidation(): void {
        if (this.validationIntervalId) {
            clearInterval(this.validationIntervalId);
        }

        this.validationIntervalId = setInterval(async () => {
            if (this._state().isActivated && navigator.onLine) {
                console.log('[License] Running background validation...');
                await this.validate();
            }
        }, LICENSE_CONFIG.VALIDATION_INTERVAL_MS);

        // Also validate when coming back online
        window.addEventListener('online', () => {
            if (this._state().isActivated) {
                console.log('[License] Back online, validating...');
                this.validate();
            }
        });
    }

    // ==================== Private Helper Methods ====================

    private updateState(partial: Partial<LicenseState>): void {
        this._state.update((state) => ({ ...state, ...partial }));
    }

    private async getOrCreateDeviceId(): Promise<string> {
        let deviceId = await this.getDeviceId();

        if (!deviceId) {
            deviceId = this.generateUUID();
            await this.saveDeviceId(deviceId);
        }

        this.deviceId = deviceId;
        return deviceId;
    }

    private generateUUID(): string {
        // Use crypto API for secure UUID generation
        if (crypto?.randomUUID) {
            return crypto.randomUUID();
        }

        // Fallback UUID v4 generation
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
            const r = (Math.random() * 16) | 0;
            const v = c === 'x' ? r : (r & 0x3) | 0x8;
            return v.toString(16);
        });
    }

    // ==================== Storage Methods ====================

    private async getDeviceId(): Promise<string | null> {
        if (window.desktop?.license?.getDeviceId) {
            return await window.desktop.license.getDeviceId();
        }
        return localStorage.getItem(LICENSE_CONFIG.STORAGE_KEYS.DEVICE_ID);
    }

    private async saveDeviceId(deviceId: string): Promise<void> {
        if (window.desktop?.license?.saveLicenseData) {
            await window.desktop.license.saveLicenseData({ deviceId });
        }
        localStorage.setItem(LICENSE_CONFIG.STORAGE_KEYS.DEVICE_ID, deviceId);
    }

    private async getToken(): Promise<string | null> {
        if (window.desktop?.license?.getToken) {
            return await window.desktop.license.getToken();
        }
        // Fallback to localStorage (less secure, but works in browser)
        return localStorage.getItem('sejoli_auth_token');
    }

    private async saveToken(token: string): Promise<void> {
        if (window.desktop?.license?.saveToken) {
            await window.desktop.license.saveToken(token);
        }
        // Fallback to localStorage
        localStorage.setItem('sejoli_auth_token', token);
    }

    private async clearToken(): Promise<void> {
        if (window.desktop?.license?.clearToken) {
            await window.desktop.license.clearToken();
        }
        localStorage.removeItem('sejoli_auth_token');
    }

    private getLicenseKey(): string | null {
        return localStorage.getItem(LICENSE_CONFIG.STORAGE_KEYS.LICENSE_KEY);
    }

    private async saveLicenseData(data: {
        licenseKey: string;
        email: string;
        productName?: string;
        subscriptionStatus?: string;
        expirationDate?: string;
    }): Promise<void> {
        localStorage.setItem(LICENSE_CONFIG.STORAGE_KEYS.LICENSE_KEY, data.licenseKey);
        localStorage.setItem(LICENSE_CONFIG.STORAGE_KEYS.USER_EMAIL, data.email);
        localStorage.setItem(LICENSE_CONFIG.STORAGE_KEYS.LAST_VALIDATED, Date.now().toString());

        if (window.desktop?.license?.saveLicenseData) {
            await window.desktop.license.saveLicenseData({
                licenseKey: data.licenseKey,
                email: data.email,
                productName: data.productName || '',
                subscriptionStatus: data.subscriptionStatus || '',
                expirationDate: data.expirationDate || '',
            });
        }
    }

    private async clearLicenseData(): Promise<void> {
        await this.clearToken();
        localStorage.removeItem(LICENSE_CONFIG.STORAGE_KEYS.DEVICE_ID);
        localStorage.removeItem(LICENSE_CONFIG.STORAGE_KEYS.LICENSE_KEY);
        localStorage.removeItem(LICENSE_CONFIG.STORAGE_KEYS.USER_EMAIL);
        localStorage.removeItem(LICENSE_CONFIG.STORAGE_KEYS.LAST_VALIDATED);
        localStorage.removeItem(LICENSE_CONFIG.STORAGE_KEYS.CACHED_STATUS);
        this.deviceId = null;

        if (window.desktop?.license?.clearLicenseData) {
            await window.desktop.license.clearLicenseData();
        }
    }

    private cacheValidStatus(valid: boolean): void {
        localStorage.setItem(
            LICENSE_CONFIG.STORAGE_KEYS.CACHED_STATUS,
            JSON.stringify({ valid, timestamp: Date.now() })
        );
    }

    private getCachedStatus(): boolean | null {
        const cached = localStorage.getItem(LICENSE_CONFIG.STORAGE_KEYS.CACHED_STATUS);
        if (!cached) return null;

        try {
            const data = JSON.parse(cached);
            // Cache valid for 7 days in offline mode
            const maxAge = 7 * 24 * 60 * 60 * 1000;
            if (Date.now() - data.timestamp < maxAge) {
                return data.valid;
            }
        } catch {
            return null;
        }
        return null;
    }
}
