// Fix: Populating the content for data models.
export type UserRole = 'administrator' | 'manager' | 'sales' | 'cashier';

export type DateRangePreset = 'this_week' | 'this_month' | 'this_year' | 'last_year' | 'custom' | 'all_time';

export interface User {
  id: number;
  name: string;
  email: string;
  role: UserRole;
}

export interface CompanyProfile {
  nama: string;
  alamat: string;
  telepon: string;
  email: string;
  website: string;
  npwp?: string;
  logo_url?: string;
  ttd_admin_nama?: string;
  footer_cetak?: string;
  nomor_format: {
    faktur: string;
    kuitansi: string;
  };
}

export interface Project {
  id: number;
  name: string;
  location: string;
  description?: string;
}

export interface Lot {
  id: number;
  project_id: number;
  block: string;
  lot_number: string;
  area: number;
  base_price: number;
  status: 'available' | 'sold';
}

export interface Customer {
  id: number;
  name: string;
  phone: string;
  address: string;
}

export interface Salesman {
  id: number;
  name: string;
  phone: string;
}

export interface PaymentMethod {
    id: string;
    name: string;
}

export type SaleStatus = 'draft' | 'active' | 'paid_off' | 'cancelled';
export type DPStatus = 'unpaid' | 'paid';
export type FollowUpStatus = 'normal' | 'follow_up' | 'urgent';
export type BillingStatus = 'urgent' | 'attention' | 'safe' | 'default';

export interface Sale {
  id: number; 
  invoice_no: string;
  invoice_date: string; // ISO date string
  customer_id: number;
  kavling_id: number; 
  sales_id: number; 
  metode_id: string; 
  harga_dasar: number;
  promo_diskon: number;
  harga_netto: number;
  uang_muka_persen: number; 
  uang_muka_rp: number;
  dp_terbayar: number;
  dp_sisa: number;
  biaya_ppjb: number;
  biaya_shm: number;
  biaya_lain_total: number;
  grand_total: number;
  tenor: number;
  jatuh_tempo_hari: number;
  status_penjualan: SaleStatus;
  status_dp: DPStatus;
  status_followup: FollowUpStatus;
  catatan?: string;
}


export type InstallmentStatus = 'paid' | 'unpaid' | 'partial' | 'overdue' | 'cancelled';

export interface Installment {
  id: number;
  sale_id: number;
  installment_number: number;
  due_date: string; // ISO date string
  amount: number;
  paid_amount: number;
  payment_date?: string; // ISO date string
  status: InstallmentStatus;
}

export interface Payment {
  id: number;
  receipt_no: string;
  sale_id: number;
  payment_date: string; // ISO date string
  amount: number;
  payment_for: string; // e.g., 'Uang Muka', 'Angsuran ke-5', 'Pembayaran Fleksibel'
}

export interface ForecastData {
  bulan: string; // YYYY-MM
  proyeksi_penjualan: number;
  unit_terjual: number;
  proyeksi_penjualan_pesimis?: number;
  proyeksi_penjualan_optimis?: number;
}