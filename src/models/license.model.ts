/**
 * Sejoli License Models
 * Interfaces for license management API responses and requests
 */

// Subscription status values from Sejoli API
export type SubscriptionStatus = 'active' | 'expired' | 'completed';

// License activation request body
export interface LicenseActivationRequest {
  user_email: string;
  user_pass: string;
  license: string;
  string: string; // Device ID (UUID)
}

// License validation request body
export interface LicenseValidationRequest {
  string: string; // Device ID
}

// License reset/delete request body
export interface LicenseResetRequest {
  license: string;
  string: string; // Device ID
}

// Response data from Sejoli API
export interface LicenseResponseData {
  token?: string;
  subscription_status?: SubscriptionStatus;
  expiration_date?: string;
  product_name?: string;
  license_key?: string;
  order_id?: string;
}

// Standard Sejoli API response
export interface LicenseResponse {
  valid: boolean;
  message: string;
  data?: LicenseResponseData;
}

// Local license state for the app
export interface LicenseState {
  isValid: boolean;
  isLoading: boolean;
  isActivated: boolean;
  errorMessage: string | null;
  subscriptionStatus: SubscriptionStatus | null;
  expirationDate: string | null;
  productName: string | null;
  lastValidated: number | null; // Unix timestamp
}

// Error message mappings for user-friendly display
export const LICENSE_ERROR_ACTIONS: Record<string, string> = {
  'Email atau password salah': 'Periksa kembali email dan password Anda',
  'Token kadaluarsa, silakan login ulang': 'Silakan aktivasi ulang',
  'Token tidak valid': 'Silakan aktivasi ulang',
  'Lisensi tidak ditemukan': 'Periksa kembali kode lisensi Anda',
  'String penanda tidak boleh kosong': 'Terjadi kesalahan sistem, coba restart aplikasi',
  'Lisensi ini bukan milik anda': 'Gunakan akun yang benar untuk lisensi ini',
  'Berlangganan kadaluarsa': 'Perpanjang langganan di website',
  'Lisensi tidak aktif atau berlangganan kadaluarsa': 'Perpanjang langganan di website',
};
