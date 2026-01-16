import { Injectable, signal, computed, effect } from '@angular/core';
import {
  User,
  Project,
  Lot,
  Customer,
  Salesman,
  Sale,
  Installment,
  CompanyProfile,
  SaleStatus,
  InstallmentStatus,
  PaymentMethod,
  DPStatus,
  FollowUpStatus,
  DateRangePreset,
  Payment,
  BillingStatus,
  ForecastData
} from '../models/data.models';

const INITIAL_COMPANY_PROFILE: CompanyProfile = {
  nama: 'Perusahaan Properti Anda',
  alamat: '',
  telepon: '',
  email: '',
  website: '',
  npwp: '',
  logo_url: '',
  ttd_admin_nama: 'Admin',
  footer_cetak: 'Terima kasih.',
  nomor_format: {
    faktur: 'INV/{YYYY}/{MM}/{####}',
    kuitansi: 'KW/{YYYY}/{MM}/{####}',
  },
};

// Mock Data
const MOCK_USERS: User[] = [
  { id: 1, name: 'Admin User', email: 'admin@example.com', role: 'administrator' },
  { id: 2, name: 'Manager User', email: 'manager@example.com', role: 'manager' },
  { id: 3, name: 'Sales User', email: 'sales@example.com', role: 'sales' },
  { id: 4, name: 'Cashier User', email: 'cashier@example.com', role: 'cashier' },
];

const DEFAULT_USER: User = MOCK_USERS[0];

const MOCK_COMPANY_PROFILE: CompanyProfile = {
  nama: 'Nama Perusahaan',
  alamat: 'Cisarua, Kabupaten Bogor, Jawa Barat 16750',
  telepon: '021-12345678',
  email: 'emailperusahaan@gmail.com',
  website: 'www.propertisejahtera.com',
  npwp: '01.234.567.8-901.000',
  logo_url: '',
  ttd_admin_nama: 'Admin Keuangan',
  footer_cetak: 'Terima kasih atas pembayaran Anda.',
  nomor_format: {
    faktur: 'INV/{YYYY}/{MM}/{####}',
    kuitansi: 'KW/{YYYY}/{MM}/{####}',
  },
};

const MOCK_PROJECTS: Project[] = [
    { id: 1, name: 'Kavling Harmoni Alam', location: 'Ciawi, Bogor', description: 'Pengembangan tahap 1 seluas 2 hektar.' },
    { id: 2, name: 'Kavling Mutiara Residence', location: 'Sentul, Bogor', description: 'Kawasan premium dengan fasilitas lengkap.' },
];

const MOCK_LOTS: Lot[] = [
    // Project 1: Kavling Harmoni Alam (30 lots) - Range: 75jt - 180jt
    { id: 1, project_id: 1, block: 'A', lot_number: '1', area: 120, base_price: 120000000, status: 'available' },
    { id: 2, project_id: 1, block: 'A', lot_number: '2', area: 130, base_price: 135000000, status: 'available' },
    { id: 3, project_id: 1, block: 'A', lot_number: '3', area: 140, base_price: 145000000, status: 'available' },
    { id: 4, project_id: 1, block: 'A', lot_number: '4', area: 150, base_price: 155000000, status: 'available' },
    { id: 5, project_id: 1, block: 'A', lot_number: '5', area: 160, base_price: 170000000, status: 'available' },
    { id: 6, project_id: 1, block: 'B', lot_number: '1', area: 120, base_price: 125000000, status: 'available' },
    { id: 7, project_id: 1, block: 'B', lot_number: '2', area: 130, base_price: 140000000, status: 'available' },
    { id: 8, project_id: 1, block: 'B', lot_number: '3', area: 140, base_price: 150000000, status: 'available' },
    { id: 9, project_id: 1, block: 'B', lot_number: '4', area: 150, base_price: 160000000, status: 'available' },
    { id: 10, project_id: 1, block: 'B', lot_number: '5', area: 160, base_price: 175000000, status: 'available' },
    { id: 11, project_id: 1, block: 'C', lot_number: '1', area: 100, base_price: 75000000, status: 'available' },
    { id: 12, project_id: 1, block: 'C', lot_number: '2', area: 100, base_price: 80000000, status: 'available' },
    { id: 13, project_id: 1, block: 'C', lot_number: '3', area: 110, base_price: 95000000, status: 'available' },
    { id: 14, project_id: 1, block: 'C', lot_number: '4', area: 110, base_price: 100000000, status: 'available' },
    { id: 15, project_id: 1, block: 'C', lot_number: '5', area: 120, base_price: 115000000, status: 'available' },
    { id: 16, project_id: 1, block: 'A', lot_number: '6', area: 125, base_price: 130000000, status: 'available' },
    { id: 17, project_id: 1, block: 'A', lot_number: '7', area: 135, base_price: 140000000, status: 'available' },
    { id: 18, project_id: 1, block: 'A', lot_number: '8', area: 145, base_price: 150000000, status: 'available' },
    { id: 19, project_id: 1, block: 'A', lot_number: '9', area: 155, base_price: 165000000, status: 'available' },
    { id: 20, project_id: 1, block: 'A', lot_number: '10', area: 165, base_price: 180000000, status: 'available' },
    { id: 21, project_id: 1, block: 'B', lot_number: '6', area: 125, base_price: 135000000, status: 'available' },
    { id: 22, project_id: 1, block: 'B', lot_number: '7', area: 135, base_price: 145000000, status: 'available' },
    { id: 23, project_id: 1, block: 'B', lot_number: '8', area: 145, base_price: 155000000, status: 'available' },
    { id: 24, project_id: 1, block: 'B', lot_number: '9', area: 155, base_price: 170000000, status: 'available' },
    { id: 25, project_id: 1, block: 'B', lot_number: '10', area: 165, base_price: 180000000, status: 'available' },
    { id: 26, project_id: 1, block: 'C', lot_number: '6', area: 125, base_price: 120000000, status: 'available' },
    { id: 27, project_id: 1, block: 'C', lot_number: '7', area: 135, base_price: 130000000, status: 'available' },
    { id: 28, project_id: 1, block: 'C', lot_number: '8', area: 145, base_price: 140000000, status: 'available' },
    { id: 29, project_id: 1, block: 'C', lot_number: '9', area: 155, base_price: 155000000, status: 'available' },
    { id: 30, project_id: 1, block: 'C', lot_number: '10', area: 165, base_price: 170000000, status: 'available' },

    // Project 2: Kavling Mutiara Residence (30 lots) - Range: 150jt - 280jt
    { id: 31, project_id: 2, block: 'D', lot_number: '1', area: 100, base_price: 150000000, status: 'available' },
    { id: 32, project_id: 2, block: 'D', lot_number: '2', area: 110, base_price: 165000000, status: 'available' },
    { id: 33, project_id: 2, block: 'D', lot_number: '3', area: 120, base_price: 180000000, status: 'available' },
    { id: 34, project_id: 2, block: 'D', lot_number: '4', area: 130, base_price: 195000000, status: 'available' },
    { id: 35, project_id: 2, block: 'D', lot_number: '5', area: 140, base_price: 210000000, status: 'available' },
    { id: 36, project_id: 2, block: 'E', lot_number: '1', area: 100, base_price: 155000000, status: 'available' },
    { id: 37, project_id: 2, block: 'E', lot_number: '2', area: 110, base_price: 170000000, status: 'available' },
    { id: 38, project_id: 2, block: 'E', lot_number: '3', area: 120, base_price: 185000000, status: 'available' },
    { id: 39, project_id: 2, block: 'E', lot_number: '4', area: 130, base_price: 200000000, status: 'available' },
    { id: 40, project_id: 2, block: 'E', lot_number: '5', area: 140, base_price: 215000000, status: 'available' },
    { id: 41, project_id: 2, block: 'F', lot_number: '1', area: 150, base_price: 225000000, status: 'available' },
    { id: 42, project_id: 2, block: 'F', lot_number: '2', area: 160, base_price: 240000000, status: 'available' },
    { id: 43, project_id: 2, block: 'F', lot_number: '3', area: 170, base_price: 255000000, status: 'available' },
    { id: 44, project_id: 2, block: 'F', lot_number: '4', area: 180, base_price: 270000000, status: 'available' },
    { id: 45, project_id: 2, block: 'F', lot_number: '5', area: 190, base_price: 280000000, status: 'available' },
    { id: 46, project_id: 2, block: 'D', lot_number: '6', area: 105, base_price: 158000000, status: 'available' },
    { id: 47, project_id: 2, block: 'D', lot_number: '7', area: 115, base_price: 172000000, status: 'available' },
    { id: 48, project_id: 2, block: 'D', lot_number: '8', area: 125, base_price: 188000000, status: 'available' },
    { id: 49, project_id: 2, block: 'D', lot_number: '9', area: 135, base_price: 202000000, status: 'available' },
    { id: 50, project_id: 2, block: 'D', lot_number: '10', area: 145, base_price: 218000000, status: 'available' },
    { id: 51, project_id: 2, block: 'E', lot_number: '6', area: 105, base_price: 162000000, status: 'available' },
    { id: 52, project_id: 2, block: 'E', lot_number: '7', area: 115, base_price: 178000000, status: 'available' },
    { id: 53, project_id: 2, block: 'E', lot_number: '8', area: 125, base_price: 192000000, status: 'available' },
    { id: 54, project_id: 2, block: 'E', lot_number: '9', area: 135, base_price: 208000000, status: 'available' },
    { id: 55, project_id: 2, block: 'E', lot_number: '10', area: 145, base_price: 222000000, status: 'available' },
    { id: 56, project_id: 2, block: 'F', lot_number: '6', area: 155, base_price: 235000000, status: 'available' },
    { id: 57, project_id: 2, block: 'F', lot_number: '7', area: 165, base_price: 250000000, status: 'available' },
    { id: 58, project_id: 2, block: 'F', lot_number: '8', area: 175, base_price: 265000000, status: 'available' },
    { id: 59, project_id: 2, block: 'F', lot_number: '9', area: 185, base_price: 275000000, status: 'available' },
    { id: 60, project_id: 2, block: 'F', lot_number: '10', area: 195, base_price: 280000000, status: 'available' },
];

const MOCK_CUSTOMERS: Customer[] = [
    { id: 1, name: 'Budi Santoso', phone: '081234567890', address: 'Jl. Merdeka No. 1, Jakarta' },
    { id: 2, name: 'Siti Aminah', phone: '081122334455', address: 'Jl. Pahlawan No. 2, Surabaya' },
    { id: 3, name: 'Chandra Wijaya', phone: '081333444555', address: 'Jl. Asia Afrika No. 8, Bandung' },
    { id: 4, name: 'Dewi Lestari', phone: '081444555666', address: 'Jl. Gajah Mada No. 10, Semarang' },
    { id: 5, name: 'Eko Prasetyo', phone: '081555666777', address: 'Jl. Diponegoro No. 12, Yogyakarta' },
    { id: 6, name: 'Fitriani', phone: '081666777888', address: 'Jl. Sudirman No. 14, Medan' },
    { id: 7, name: 'Gunawan', phone: '081777888999', address: 'Jl. Thamrin No. 16, Jakarta Pusat' },
    { id: 8, name: 'Hasanah', phone: '081888999000', address: 'Jl. Kuta No. 18, Bali' },
    { id: 9, name: 'Indra Kusuma', phone: '081999000111', address: 'Jl. Cihampelas No. 20, Bandung' },
    { id: 10, name: 'Joko Susilo', phone: '082111222333', address: 'Jl. Patriot No. 22, Bekasi' },
    { id: 11, name: 'Kartika Sari', phone: '082222333444', address: 'Jl. Pahlawan Seribu No. 24, Tangerang' },
    { id: 12, name: 'Lia Anggraini', phone: '082333444555', address: 'Jl. Dago No. 26, Bandung' },
    { id: 13, name: 'Muhammad Ali', phone: '085111222333', address: 'Jl. Cempaka Putih No. 28, Jakarta' },
    { id: 14, name: 'Nadia Putri', phone: '085222333444', address: 'Jl. Bogor Raya No. 30, Bogor' },
    { id: 15, name: 'Omar Abdullah', phone: '085333444555', address: 'Jl. Veteran No. 32, Surabaya' },
    { id: 16, name: 'Putri Ayu', phone: '087111222333', address: 'Jl. Kenari No. 34, Yogyakarta' },
    { id: 17, name: 'Qodir Jaelani', phone: '087222333444', address: 'Jl. Melati No. 36, Depok' },
    { id: 18, name: 'Rahmat Hidayat', phone: '087333444555', address: 'Jl. Mawar No. 38, Bekasi' },
    { id: 19, name: 'Sari Puspita', phone: '089111222333', address: 'Jl. Seminyak No. 40, Bali' },
    { id: 20, name: 'Taufik Hidayat', phone: '089222333444', address: 'Jl. Sudirman No. 42, Bandung' },
];

const MOCK_SALESMEN: Salesman[] = [
    { id: 1, name: 'Andi Wijaya', phone: '085678901234' }, // Top Performer
    { id: 2, name: 'Rina Marlina', phone: '087712345678' }, // Solid
    { id: 3, name: 'Doni Firmansyah', phone: '081298765432' }, // Average
    { id: 4, name: 'Citra Dewi', phone: '087811223344' }, // Average
    { id: 5, name: 'Bambang Hartono', phone: '089955554444' }, // Needs Improvement
];

const MOCK_PAYMENT_METHODS: PaymentMethod[] = [
    { id: 'MP01', name: 'Angsuran In-house' },
    { id: 'MP02', name: 'Cash Keras' },
    { id: 'MP03', name: 'KPR Bank' },
];

const MOCK_SALES: Sale[] = [
  // --- 2024: Tahun Perkenalan (16 Units | Total Omzet ~2.4 M) ---
  // 2024 Q1 - Slow Start (1 Unit)
  { id: 1, invoice_no: 'INV-202401-0001', invoice_date: '2024-01-20T10:00:00Z', customer_id: 1, kavling_id: 1, sales_id: 3, metode_id: 'MP01', harga_dasar: 120000000, promo_diskon: 0, harga_netto: 120000000, uang_muka_persen: 20, uang_muka_rp: 24000000, dp_terbayar: 24000000, dp_sisa: 0, biaya_ppjb: 5000000, biaya_shm: 10000000, biaya_lain_total: 0, grand_total: 135000000, tenor: 24, jatuh_tempo_hari: 15, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },

  // 2024 Q2 - Warming Up (4 Units)
  { id: 2, invoice_no: 'INV-202404-0002', invoice_date: '2024-04-15T11:00:00Z', customer_id: 2, kavling_id: 31, sales_id: 2, metode_id: 'MP01', harga_dasar: 150000000, promo_diskon: 5000000, harga_netto: 145000000, uang_muka_persen: 30, uang_muka_rp: 43500000, dp_terbayar: 43500000, dp_sisa: 0, biaya_ppjb: 5000000, biaya_shm: 12000000, biaya_lain_total: 0, grand_total: 162000000, tenor: 12, jatuh_tempo_hari: 22, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 3, invoice_no: 'INV-202405-0003', invoice_date: '2024-05-05T12:00:00Z', customer_id: 3, kavling_id: 2, sales_id: 1, metode_id: 'MP02', harga_dasar: 135000000, promo_diskon: 10000000, harga_netto: 125000000, uang_muka_persen: 100, uang_muka_rp: 125000000, dp_terbayar: 125000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 125000000, tenor: 0, jatuh_tempo_hari: 1, status_penjualan: 'paid_off', status_dp: 'paid', status_followup: 'normal' },
  { id: 4, invoice_no: 'INV-2024-06-0004', invoice_date: '2024-06-28T10:00:00Z', customer_id: 4, kavling_id: 6, sales_id: 4, metode_id: 'MP01', harga_dasar: 125000000, promo_diskon: 0, harga_netto: 125000000, uang_muka_persen: 20, uang_muka_rp: 25000000, dp_terbayar: 25000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 125000000, tenor: 24, jatuh_tempo_hari: 28, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 5, invoice_no: 'INV-2024-06-0005', invoice_date: '2024-06-28T14:00:00Z', customer_id: 5, kavling_id: 7, sales_id: 1, metode_id: 'MP01', harga_dasar: 140000000, promo_diskon: 0, harga_netto: 140000000, uang_muka_persen: 20, uang_muka_rp: 28000000, dp_terbayar: 28000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 140000000, tenor: 12, jatuh_tempo_hari: 28, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },

  // 2024 Q3 - Quiet Period (2 Units)
  { id: 6, invoice_no: 'INV-202407-0006', invoice_date: '2024-07-15T11:00:00Z', customer_id: 6, kavling_id: 32, sales_id: 2, metode_id: 'MP01', harga_dasar: 165000000, promo_diskon: 0, harga_netto: 165000000, uang_muka_persen: 20, uang_muka_rp: 33000000, dp_terbayar: 33000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 165000000, tenor: 24, jatuh_tempo_hari: 15, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 7, invoice_no: 'INV-202409-0007', invoice_date: '2024-09-05T12:00:00Z', customer_id: 7, kavling_id: 8, sales_id: 1, metode_id: 'MP03', harga_dasar: 150000000, promo_diskon: 0, harga_netto: 150000000, uang_muka_persen: 20, uang_muka_rp: 30000000, dp_terbayar: 10000000, dp_sisa: 20000000, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 150000000, tenor: 12, jatuh_tempo_hari: 25, status_penjualan: 'active', status_dp: 'unpaid', status_followup: 'urgent' },

  // 2024 Q4 - Year End Rush (9 Units)
  { id: 8, invoice_no: 'INV-202410-0008', invoice_date: '2024-10-10T10:00:00Z', customer_id: 8, kavling_id: 3, sales_id: 1, metode_id: 'MP01', harga_dasar: 145000000, promo_diskon: 0, harga_netto: 145000000, uang_muka_persen: 25, uang_muka_rp: 36250000, dp_terbayar: 36250000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 145000000, tenor: 36, jatuh_tempo_hari: 10, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 9, invoice_no: 'INV-202411-0009', invoice_date: '2024-11-02T10:00:00Z', customer_id: 9, kavling_id: 33, sales_id: 4, metode_id: 'MP01', harga_dasar: 180000000, promo_diskon: 5000000, harga_netto: 175000000, uang_muka_persen: 20, uang_muka_rp: 35000000, dp_terbayar: 35000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 175000000, tenor: 12, jatuh_tempo_hari: 1, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 10, invoice_no: 'INV-202411-0010', invoice_date: '2024-11-20T11:00:00Z', customer_id: 10, kavling_id: 34, sales_id: 2, metode_id: 'MP01', harga_dasar: 195000000, promo_diskon: 0, harga_netto: 195000000, uang_muka_persen: 20, uang_muka_rp: 39000000, dp_terbayar: 39000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 195000000, tenor: 24, jatuh_tempo_hari: 5, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 11, invoice_no: 'INV-202412-0011', invoice_date: '2024-12-05T12:00:00Z', customer_id: 11, kavling_id: 4, sales_id: 1, metode_id: 'MP01', harga_dasar: 155000000, promo_diskon: 0, harga_netto: 155000000, uang_muka_persen: 20, uang_muka_rp: 31000000, dp_terbayar: 31000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 155000000, tenor: 12, jatuh_tempo_hari: 10, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 12, invoice_no: 'INV-202412-0012', invoice_date: '2024-12-15T13:00:00Z', customer_id: 12, kavling_id: 35, sales_id: 2, metode_id: 'MP01', harga_dasar: 210000000, promo_diskon: 0, harga_netto: 210000000, uang_muka_persen: 30, uang_muka_rp: 63000000, dp_terbayar: 63000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 210000000, tenor: 36, jatuh_tempo_hari: 22, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 13, invoice_no: 'INV-202412-0013', invoice_date: '2024-12-28T10:00:00Z', customer_id: 13, kavling_id: 36, sales_id: 1, metode_id: 'MP02', harga_dasar: 155000000, promo_diskon: 15000000, harga_netto: 140000000, uang_muka_persen: 100, uang_muka_rp: 140000000, dp_terbayar: 140000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 140000000, tenor: 0, jatuh_tempo_hari: 1, status_penjualan: 'paid_off', status_dp: 'paid', status_followup: 'normal' },
  { id: 14, invoice_no: 'INV-202412-0014', invoice_date: '2024-12-28T11:00:00Z', customer_id: 14, kavling_id: 9, sales_id: 5, metode_id: 'MP01', harga_dasar: 160000000, promo_diskon: 0, harga_netto: 160000000, uang_muka_persen: 20, uang_muka_rp: 32000000, dp_terbayar: 32000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 160000000, tenor: 12, jatuh_tempo_hari: 28, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 15, invoice_no: 'INV-202412-0015', invoice_date: '2024-12-29T10:00:00Z', customer_id: 15, kavling_id: 10, sales_id: 1, metode_id: 'MP01', harga_dasar: 175000000, promo_diskon: 0, harga_netto: 175000000, uang_muka_persen: 20, uang_muka_rp: 35000000, dp_terbayar: 35000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 175000000, tenor: 24, jatuh_tempo_hari: 1, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 16, invoice_no: 'INV-202412-0016', invoice_date: '2024-12-29T15:00:00Z', customer_id: 16, kavling_id: 11, sales_id: 2, metode_id: 'MP01', harga_dasar: 75000000, promo_diskon: 0, harga_netto: 75000000, uang_muka_persen: 30, uang_muka_rp: 22500000, dp_terbayar: 22500000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 75000000, tenor: 36, jatuh_tempo_hari: 22, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },

  // --- 2025: Tahun Pertumbuhan (37 Units | Total Omzet ~6.7 M) ---
  // 2025 Q1 - Slow (3 Units)
  { id: 17, invoice_no: 'INV-202501-0017', invoice_date: '2025-01-18T11:00:00Z', customer_id: 17, kavling_id: 12, sales_id: 3, metode_id: 'MP01', harga_dasar: 80000000, promo_diskon: 0, harga_netto: 80000000, uang_muka_persen: 20, uang_muka_rp: 16000000, dp_terbayar: 16000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 80000000, tenor: 24, jatuh_tempo_hari: 28, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 18, invoice_no: 'INV-202502-0018', invoice_date: '2025-02-10T10:00:00Z', customer_id: 18, kavling_id: 37, sales_id: 1, metode_id: 'MP01', harga_dasar: 170000000, promo_diskon: 5000000, harga_netto: 165000000, uang_muka_persen: 20, uang_muka_rp: 33000000, dp_terbayar: 33000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 165000000, tenor: 12, jatuh_tempo_hari: 1, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 19, invoice_no: 'INV-202503-0019', invoice_date: '2025-03-22T11:00:00Z', customer_id: 19, kavling_id: 13, sales_id: 2, metode_id: 'MP01', harga_dasar: 95000000, promo_diskon: 0, harga_netto: 95000000, uang_muka_persen: 25, uang_muka_rp: 23750000, dp_terbayar: 23750000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 95000000, tenor: 36, jatuh_tempo_hari: 10, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },

  // 2025 Q2 - Mid-Year Spike (9 Units)
  { id: 20, invoice_no: 'INV-202504-0020', invoice_date: '2025-04-15T10:00:00Z', customer_id: 20, kavling_id: 14, sales_id: 5, metode_id: 'MP01', harga_dasar: 100000000, promo_diskon: 0, harga_netto: 100000000, uang_muka_persen: 20, uang_muka_rp: 20000000, dp_terbayar: 5000000, dp_sisa: 15000000, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 100000000, tenor: 24, jatuh_tempo_hari: 25, status_penjualan: 'active', status_dp: 'unpaid', status_followup: 'urgent' },
  { id: 21, invoice_no: 'INV-202505-0021', invoice_date: '2025-05-05T10:00:00Z', customer_id: 1, kavling_id: 38, sales_id: 1, metode_id: 'MP02', harga_dasar: 185000000, promo_diskon: 10000000, harga_netto: 175000000, uang_muka_persen: 100, uang_muka_rp: 175000000, dp_terbayar: 175000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 175000000, tenor: 0, jatuh_tempo_hari: 1, status_penjualan: 'paid_off', status_dp: 'paid', status_followup: 'normal' },
  { id: 22, invoice_no: 'INV-202505-0022', invoice_date: '2025-05-25T11:00:00Z', customer_id: 2, kavling_id: 15, sales_id: 4, metode_id: 'MP01', harga_dasar: 115000000, promo_diskon: 0, harga_netto: 115000000, uang_muka_persen: 20, uang_muka_rp: 23000000, dp_terbayar: 23000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 115000000, tenor: 12, jatuh_tempo_hari: 1, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 23, invoice_no: 'INV-202506-0023', invoice_date: '2025-06-10T12:00:00Z', customer_id: 3, kavling_id: 39, sales_id: 2, metode_id: 'MP01', harga_dasar: 200000000, promo_diskon: 0, harga_netto: 200000000, uang_muka_persen: 20, uang_muka_rp: 40000000, dp_terbayar: 40000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 200000000, tenor: 24, jatuh_tempo_hari: 3, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 24, invoice_no: 'INV-202506-0024', invoice_date: '2025-06-20T10:00:00Z', customer_id: 4, kavling_id: 16, sales_id: 1, metode_id: 'MP01', harga_dasar: 130000000, promo_diskon: 0, harga_netto: 130000000, uang_muka_persen: 20, uang_muka_rp: 26000000, dp_terbayar: 26000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 130000000, tenor: 12, jatuh_tempo_hari: 6, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 25, invoice_no: 'INV-202506-0025', invoice_date: '2025-06-20T11:00:00Z', customer_id: 5, kavling_id: 17, sales_id: 2, metode_id: 'MP01', harga_dasar: 140000000, promo_diskon: 0, harga_netto: 140000000, uang_muka_persen: 20, uang_muka_rp: 28000000, dp_terbayar: 28000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 140000000, tenor: 24, jatuh_tempo_hari: 6, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 26, invoice_no: 'INV-202506-0026', invoice_date: '2025-06-28T11:00:00Z', customer_id: 6, kavling_id: 40, sales_id: 3, metode_id: 'MP01', harga_dasar: 215000000, promo_diskon: 0, harga_netto: 215000000, uang_muka_persen: 30, uang_muka_rp: 64500000, dp_terbayar: 64500000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 215000000, tenor: 36, jatuh_tempo_hari: 9, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 27, invoice_no: 'INV-202506-0027', invoice_date: '2025-06-28T12:00:00Z', customer_id: 7, kavling_id: 18, sales_id: 1, metode_id: 'MP01', harga_dasar: 150000000, promo_diskon: 0, harga_netto: 150000000, uang_muka_persen: 20, uang_muka_rp: 30000000, dp_terbayar: 30000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 150000000, tenor: 36, jatuh_tempo_hari: 9, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 28, invoice_no: 'INV-202506-0028', invoice_date: '2025-06-28T13:00:00Z', customer_id: 8, kavling_id: 19, sales_id: 1, metode_id: 'MP01', harga_dasar: 165000000, promo_diskon: 0, harga_netto: 165000000, uang_muka_persen: 20, uang_muka_rp: 33000000, dp_terbayar: 33000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 165000000, tenor: 36, jatuh_tempo_hari: 9, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },

  // 2025 Q3 - Quiet, August is empty (3 Units)
  { id: 29, invoice_no: 'INV-202507-0029', invoice_date: '2025-07-21T10:00:00Z', customer_id: 9, kavling_id: 5, sales_id: 2, metode_id: 'MP01', harga_dasar: 170000000, promo_diskon: 0, harga_netto: 170000000, uang_muka_persen: 20, uang_muka_rp: 34000000, dp_terbayar: 34000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 170000000, tenor: 12, jatuh_tempo_hari: 12, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 30, invoice_no: 'INV-202509-0030', invoice_date: '2025-09-01T11:00:00Z', customer_id: 10, kavling_id: 41, sales_id: 1, metode_id: 'MP01', harga_dasar: 225000000, promo_diskon: 0, harga_netto: 225000000, uang_muka_persen: 20, uang_muka_rp: 45000000, dp_terbayar: 45000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 225000000, tenor: 24, jatuh_tempo_hari: 15, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 31, invoice_no: 'INV-202509-0031', invoice_date: '2025-09-25T10:00:00Z', customer_id: 11, kavling_id: 20, sales_id: 4, metode_id: 'MP02', harga_dasar: 180000000, promo_diskon: 15000000, harga_netto: 165000000, uang_muka_persen: 100, uang_muka_rp: 165000000, dp_terbayar: 165000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 165000000, tenor: 0, jatuh_tempo_hari: 1, status_penjualan: 'paid_off', status_dp: 'paid', status_followup: 'normal' },

  // 2025 Q4 - PEAK SEASON (22 Units)
  // -- Oktober (4 Units)
  { id: 32, invoice_no: 'INV-202510-0032', invoice_date: '2025-10-05T11:00:00Z', customer_id: 12, kavling_id: 42, sales_id: 2, metode_id: 'MP01', harga_dasar: 240000000, promo_diskon: 0, harga_netto: 240000000, uang_muka_persen: 20, uang_muka_rp: 48000000, dp_terbayar: 48000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 240000000, tenor: 36, jatuh_tempo_hari: 23, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 33, invoice_no: 'INV-202510-0033', invoice_date: '2025-10-20T10:00:00Z', customer_id: 13, kavling_id: 21, sales_id: 1, metode_id: 'MP01', harga_dasar: 135000000, promo_diskon: 0, harga_netto: 135000000, uang_muka_persen: 20, uang_muka_rp: 27000000, dp_terbayar: 27000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 135000000, tenor: 24, jatuh_tempo_hari: 26, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 34, invoice_no: 'INV-202510-0034', invoice_date: '2025-10-20T11:00:00Z', customer_id: 14, kavling_id: 22, sales_id: 1, metode_id: 'MP01', harga_dasar: 145000000, promo_diskon: 0, harga_netto: 145000000, uang_muka_persen: 20, uang_muka_rp: 29000000, dp_terbayar: 29000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 145000000, tenor: 24, jatuh_tempo_hari: 26, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 35, invoice_no: 'INV-202510-0035', invoice_date: '2025-10-22T10:00:00Z', customer_id: 15, kavling_id: 23, sales_id: 3, metode_id: 'MP01', harga_dasar: 155000000, promo_diskon: 0, harga_netto: 155000000, uang_muka_persen: 20, uang_muka_rp: 31000000, dp_terbayar: 31000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 155000000, tenor: 24, jatuh_tempo_hari: 26, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },

  // -- November PEAK (10 Units | ~2 Miliar)
  { id: 36, invoice_no: 'INV-202511-0036', invoice_date: '2025-11-05T11:00:00Z', customer_id: 16, kavling_id: 43, sales_id: 2, metode_id: 'MP01', harga_dasar: 255000000, promo_diskon: 0, harga_netto: 255000000, uang_muka_persen: 20, uang_muka_rp: 51000000, dp_terbayar: 51000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 255000000, tenor: 12, jatuh_tempo_hari: 29, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 37, invoice_no: 'INV-202511-0037', invoice_date: '2025-11-11T10:00:00Z', customer_id: 17, kavling_id: 44, sales_id: 1, metode_id: 'MP01', harga_dasar: 270000000, promo_diskon: 0, harga_netto: 270000000, uang_muka_persen: 30, uang_muka_rp: 81000000, dp_terbayar: 81000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 270000000, tenor: 36, jatuh_tempo_hari: 1, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 38, invoice_no: 'INV-202511-0038', invoice_date: '2025-11-11T14:00:00Z', customer_id: 18, kavling_id: 45, sales_id: 1, metode_id: 'MP01', harga_dasar: 280000000, promo_diskon: 0, harga_netto: 280000000, uang_muka_persen: 30, uang_muka_rp: 84000000, dp_terbayar: 84000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 280000000, tenor: 36, jatuh_tempo_hari: 1, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 39, invoice_no: 'INV-202511-0039', invoice_date: '2025-11-18T10:00:00Z', customer_id: 19, kavling_id: 24, sales_id: 2, metode_id: 'MP01', harga_dasar: 170000000, promo_diskon: 0, harga_netto: 170000000, uang_muka_persen: 20, uang_muka_rp: 34000000, dp_terbayar: 34000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 170000000, tenor: 24, jatuh_tempo_hari: 26, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 40, invoice_no: 'INV-202511-0040', invoice_date: '2025-11-25T10:00:00Z', customer_id: 20, kavling_id: 46, sales_id: 1, metode_id: 'MP01', harga_dasar: 158000000, promo_diskon: 0, harga_netto: 158000000, uang_muka_persen: 20, uang_muka_rp: 31600000, dp_terbayar: 31600000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 158000000, tenor: 24, jatuh_tempo_hari: 26, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 41, invoice_no: 'INV-202511-0041', invoice_date: '2025-11-25T11:00:00Z', customer_id: 1, kavling_id: 47, sales_id: 4, metode_id: 'MP01', harga_dasar: 172000000, promo_diskon: 0, harga_netto: 172000000, uang_muka_persen: 20, uang_muka_rp: 34400000, dp_terbayar: 34400000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 172000000, tenor: 24, jatuh_tempo_hari: 26, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 42, invoice_no: 'INV-202511-0042', invoice_date: '2025-11-25T14:00:00Z', customer_id: 2, kavling_id: 48, sales_id: 1, metode_id: 'MP01', harga_dasar: 188000000, promo_diskon: 0, harga_netto: 188000000, uang_muka_persen: 20, uang_muka_rp: 37600000, dp_terbayar: 37600000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 188000000, tenor: 24, jatuh_tempo_hari: 26, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 43, invoice_no: 'INV-202511-0043', invoice_date: '2025-11-28T10:00:00Z', customer_id: 3, kavling_id: 56, sales_id: 1, metode_id: 'MP02', harga_dasar: 235000000, promo_diskon: 20000000, harga_netto: 215000000, uang_muka_persen: 100, uang_muka_rp: 215000000, dp_terbayar: 215000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 215000000, tenor: 0, jatuh_tempo_hari: 1, status_penjualan: 'paid_off', status_dp: 'paid', status_followup: 'normal' },
  { id: 44, invoice_no: 'INV-202511-0044', invoice_date: '2025-11-29T11:00:00Z', customer_id: 4, kavling_id: 57, sales_id: 2, metode_id: 'MP01', harga_dasar: 250000000, promo_diskon: 0, harga_netto: 250000000, uang_muka_persen: 20, uang_muka_rp: 50000000, dp_terbayar: 50000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 250000000, tenor: 12, jatuh_tempo_hari: 29, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 45, invoice_no: 'INV-202511-0045', invoice_date: '2025-11-30T10:00:00Z', customer_id: 5, kavling_id: 58, sales_id: 1, metode_id: 'MP01', harga_dasar: 265000000, promo_diskon: 0, harga_netto: 265000000, uang_muka_persen: 30, uang_muka_rp: 79500000, dp_terbayar: 79500000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 265000000, tenor: 36, jatuh_tempo_hari: 1, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },

  // -- Desember (8 Units)
  { id: 46, invoice_no: 'INV-202512-0046', invoice_date: '2025-12-01T11:00:00Z', customer_id: 6, kavling_id: 25, sales_id: 3, metode_id: 'MP01', harga_dasar: 180000000, promo_diskon: 0, harga_netto: 180000000, uang_muka_persen: 20, uang_muka_rp: 36000000, dp_terbayar: 36000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 180000000, tenor: 24, jatuh_tempo_hari: 5, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 47, invoice_no: 'INV-202512-0047', invoice_date: '2025-12-10T10:00:00Z', customer_id: 7, kavling_id: 26, sales_id: 1, metode_id: 'MP01', harga_dasar: 120000000, promo_diskon: 10000000, harga_netto: 110000000, uang_muka_persen: 20, uang_muka_rp: 22000000, dp_terbayar: 22000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 110000000, tenor: 24, jatuh_tempo_hari: 10, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 48, invoice_no: 'INV-202512-0048', invoice_date: '2025-12-10T12:00:00Z', customer_id: 8, kavling_id: 27, sales_id: 2, metode_id: 'MP01', harga_dasar: 130000000, promo_diskon: 0, harga_netto: 130000000, uang_muka_persen: 20, uang_muka_rp: 26000000, dp_terbayar: 26000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 130000000, tenor: 24, jatuh_tempo_hari: 10, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 49, invoice_no: 'INV-202512-0049', invoice_date: '2025-12-20T11:00:00Z', customer_id: 9, kavling_id: 49, sales_id: 2, metode_id: 'MP01', harga_dasar: 202000000, promo_diskon: 0, harga_netto: 202000000, uang_muka_persen: 25, uang_muka_rp: 50500000, dp_terbayar: 50500000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 202000000, tenor: 36, jatuh_tempo_hari: 15, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 50, invoice_no: 'INV-202512-0050', invoice_date: '2025-12-20T14:00:00Z', customer_id: 10, kavling_id: 50, sales_id: 1, metode_id: 'MP01', harga_dasar: 218000000, promo_diskon: 0, harga_netto: 218000000, uang_muka_persen: 20, uang_muka_rp: 43600000, dp_terbayar: 43600000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 218000000, tenor: 12, jatuh_tempo_hari: 20, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 51, invoice_no: 'INV-202512-0051', invoice_date: '2025-12-22T10:00:00Z', customer_id: 11, kavling_id: 28, sales_id: 1, metode_id: 'MP01', harga_dasar: 140000000, promo_diskon: 0, harga_netto: 140000000, uang_muka_persen: 20, uang_muka_rp: 28000000, dp_terbayar: 28000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 140000000, tenor: 24, jatuh_tempo_hari: 10, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 52, invoice_no: 'INV-202512-0052', invoice_date: '2025-12-22T12:00:00Z', customer_id: 12, kavling_id: 29, sales_id: 1, metode_id: 'MP01', harga_dasar: 155000000, promo_diskon: 0, harga_netto: 155000000, uang_muka_persen: 20, uang_muka_rp: 31000000, dp_terbayar: 31000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 155000000, tenor: 24, jatuh_tempo_hari: 10, status_penjualan: 'active', status_dp: 'paid', status_followup: 'normal' },
  { id: 53, invoice_no: 'INV-202512-0053', invoice_date: '2025-12-24T10:00:00Z', customer_id: 13, kavling_id: 59, sales_id: 1, metode_id: 'MP02', harga_dasar: 275000000, promo_diskon: 25000000, harga_netto: 250000000, uang_muka_persen: 100, uang_muka_rp: 250000000, dp_terbayar: 250000000, dp_sisa: 0, biaya_ppjb: 0, biaya_shm: 0, biaya_lain_total: 0, grand_total: 250000000, tenor: 0, jatuh_tempo_hari: 1, status_penjualan: 'paid_off', status_dp: 'paid', status_followup: 'normal' },
];

interface AppData {
  companyProfile: CompanyProfile;
  projects: Project[];
  lots: Lot[];
  customers: Customer[];
  salesmen: Salesman[];
  sales: Sale[];
  installments: Installment[];
  payments: Payment[];
}

const STORAGE_KEY = 'kavlingProData';

@Injectable({
  providedIn: 'root',
})
export class DataService {
  // State Signals
  currentUser = signal<User>(DEFAULT_USER);
  companyProfile = signal<CompanyProfile>(INITIAL_COMPANY_PROFILE);
  projects = signal<Project[]>([]);
  lots = signal<Lot[]>([]);
  customers = signal<Customer[]>([]);
  salesmen = signal<Salesman[]>([]);
  paymentMethods = signal<PaymentMethod[]>(MOCK_PAYMENT_METHODS);
  sales = signal<Sale[]>([]);
  installments = signal<Installment[]>([]);
  payments = signal<Payment[]>([]);
  initialSetupDone = signal(false);

  private saveTimeoutId: ReturnType<typeof setTimeout> | null = null;
  private readonly saveDelayMs = 800;

  // Navigation signals
  requestedView = signal<string | null>(null);
  requestedSaleDetailId = signal<number | null>(null);

  // --- Forecasting State ---
  salesForecast = signal<ForecastData[] | null>(null);
  forecastLoading = signal<string | false>(false);
  forecastError = signal<string | null>(null);
  forecastDescription = signal<string | null>(null);
  // Stubs for legacy API-key modal; tidak melakukan panggilan jaringan eksternal.
  showApiKeyModal = signal(false);
  geminiApiKey = signal<string | null>(null);

  setGeminiApiKey(key: string | null) {
    this.geminiApiKey.set(key);
  }

  private appData = computed<AppData>(() => ({
    companyProfile: this.companyProfile(),
    projects: this.projects(),
    lots: this.lots(),
    customers: this.customers(),
    salesmen: this.salesmen(),
    sales: this.sales(),
    installments: this.installments(),
    payments: this.payments()
  }));

  constructor() {
    this._loadData();
    effect(() => {
      // Don't save data if the initial setup isn't complete (i.e., data is empty)
      if (this.initialSetupDone()) {
        // Track appData changes to trigger autosave.
        this.appData();
        this.scheduleSave();
      }
    });
  }

  private scheduleSave() {
    if (this.saveTimeoutId) {
      clearTimeout(this.saveTimeoutId);
    }

    this.saveTimeoutId = setTimeout(() => {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(this.appData()));
      } catch (e) {
        console.error('Error saving data to localStorage', e);
      }
    }, this.saveDelayMs);
  }

  private _loadData() {
    const savedData = localStorage.getItem(STORAGE_KEY);
    if (savedData) {
      try {
        const data: AppData = JSON.parse(savedData);
        this.companyProfile.set(data.companyProfile);
        this.projects.set(data.projects);
        this.lots.set(data.lots);
        this.customers.set(data.customers);
        this.salesmen.set(data.salesmen);
        this.sales.set(data.sales);
        this.installments.set(data.installments);
        this.payments.set(data.payments || []); // Backward compatibility
        this.initialSetupDone.set(true);
        return;
      } catch (e) {
        console.error('Failed to parse data from localStorage, falling back to clean state.', e);
        this.initialSetupDone.set(false);
      }
    } else {
        this.initialSetupDone.set(false);
    }

    // Jika belum ada data tersimpan (instalasi pertama/clean slate), biarkan kosong
    // dan tampilkan pilihan setup di UI.
  }

  loadDemoData() {
    const freshSales = JSON.parse(JSON.stringify(MOCK_SALES)) as Sale[];
    const freshLots = JSON.parse(JSON.stringify(MOCK_LOTS)) as Lot[];

    // Update lot status based on non-cancelled sales
    const soldLotIds = new Set(freshSales.filter(s => s.status_penjualan !== 'cancelled').map(s => s.kavling_id));
    freshLots.forEach(lot => {
        if (soldLotIds.has(lot.id)) {
            lot.status = 'sold';
        } else {
            lot.status = 'available';
        }
    });

    const freshInstallments: Installment[] = [];
    freshSales.forEach(sale => {
        if (sale.tenor === 0) return;

        const principal = sale.harga_netto - sale.uang_muka_rp;
        const monthlyInstallment = sale.tenor > 0 ? principal / sale.tenor : 0;
        const saleDate = new Date(sale.invoice_date);

        for(let i=1; i <= sale.tenor; i++) {
            const dueDate = new Date(saleDate.getFullYear(), saleDate.getMonth() + i, sale.jatuh_tempo_hari);

            let status: InstallmentStatus = 'unpaid';
            let paidAmount = 0;
            const today = new Date();

            if (sale.status_penjualan === 'paid_off') {
                status = 'paid';
                paidAmount = monthlyInstallment;
            } else if (sale.status_penjualan === 'cancelled') {
                status = 'cancelled';
            } else if (dueDate < today) {
                // Heuristic for demo data: Assume installments before today are paid for active sales
                status = 'paid';
                paidAmount = monthlyInstallment;
            }

            // Override for specific demo cases
            if (sale.id === 7 && i <=2) { // Example of overdue payment for Chandra
                status = 'overdue';
                paidAmount = 0;
            }
            if(sale.id === 20) { // Example of no payment made yet for Taufik
                status = 'unpaid';
                paidAmount = 0;
                if(dueDate < today) status = 'overdue';
            }

            freshInstallments.push({
                id: freshInstallments.length + 1,
                sale_id: sale.id,
                installment_number: i,
                due_date: dueDate.toISOString(),
                amount: monthlyInstallment,
                paid_amount: paidAmount,
                status: status,
                payment_date: status === 'paid' ? dueDate.toISOString() : undefined,
            });
        }
    });

    const freshPayments: Payment[] = [];
    freshSales.forEach(sale => {
      if (sale.dp_terbayar > 0) {
        freshPayments.push({
          id: freshPayments.length + 1,
          receipt_no: this._generateFormattedNumber(MOCK_COMPANY_PROFILE.nomor_format.kuitansi, freshPayments.length + 1, new Date(sale.invoice_date)),
          sale_id: sale.id,
          payment_date: sale.invoice_date,
          amount: sale.dp_terbayar,
          payment_for: 'Uang Muka (DP)',
        });
      }
    });

    freshInstallments.forEach(inst => {
      if(inst.paid_amount > 0 && inst.payment_date) {
         freshPayments.push({
            id: freshPayments.length + 1,
            receipt_no: this._generateFormattedNumber(MOCK_COMPANY_PROFILE.nomor_format.kuitansi, freshPayments.length + 1, new Date(inst.payment_date)),
            sale_id: inst.sale_id,
            payment_date: inst.payment_date,
            amount: inst.paid_amount,
            payment_for: `Angsuran ke-${inst.installment_number}`,
        });
      }
    });


    this.companyProfile.set(MOCK_COMPANY_PROFILE);
    this.projects.set(JSON.parse(JSON.stringify(MOCK_PROJECTS)));
    this.customers.set(JSON.parse(JSON.stringify(MOCK_CUSTOMERS)));
    this.salesmen.set(JSON.parse(JSON.stringify(MOCK_SALESMEN)));
    this.sales.set(freshSales);
    this.lots.set(freshLots);
    this.installments.set(freshInstallments);
    this.payments.set(freshPayments);
    this.initialSetupDone.set(true);
  }

  // Getters by ID
  getProjectById = (id: number) => this.projects().find(p => p.id === id);
  getLotById = (id: number) => this.lots().find(l => l.id === id);
  getCustomerById = (id: number) => this.customers().find(c => c.id === id);
  getSalesmanById = (id: number) => this.salesmen().find(s => s.id === id);
  getSaleById = (id: number) => this.sales().find(s => s.id === id);
  getInstallmentsForSale = (saleId: number) => this.installments().filter(i => i.sale_id === saleId).sort((a,b) => a.installment_number - b.installment_number);
  getPaymentsForSale = (saleId: number) => this.payments().filter(p => p.sale_id === saleId).sort((a,b) => new Date(b.payment_date).getTime() - new Date(a.payment_date).getTime());

  // Computed Properties for Display
  availableLots = computed(() => this.lots().filter(lot => lot.status === 'available'));

  lotsWithProject = computed(() => this.lots().map(lot => {
      const project = this.getProjectById(lot.project_id);
      return {
          ...lot,
          projectName: project ? project.name : 'N/A'
      };
  }));

  projectsWithLotCounts = computed(() => {
    const projects = this.projects();
    const lots = this.lots();
    return projects.map(project => {
      const projectLots = lots.filter(lot => lot.project_id === project.id);
      const totalLots = projectLots.length;
      const soldLots = projectLots.filter(lot => lot.status === 'sold').length;
      const availableLots = totalLots - soldLots;
      return { ...project, totalLots, soldLots, availableLots };
    });
  });

  // --- New Sales View Logic ---
  soldLotsSummary = computed(() => {
    const allInstallments = this.installments();

    return this.sales()
        .map(sale => {
            const lot = this.getLotById(sale.kavling_id);
            const project = lot ? this.getProjectById(lot.project_id) : null;
            const customer = this.getCustomerById(sale.customer_id);
            const salesman = this.getSalesmanById(sale.sales_id);
            const paymentMethod = this.paymentMethods().find(pm => pm.id === sale.metode_id);

            const bookingDateForCalc = new Date(sale.invoice_date);
            const estimatedCompletionDate = sale.tenor > 0 ? new Date(bookingDateForCalc.setMonth(bookingDateForCalc.getMonth() + sale.tenor)) : new Date(sale.invoice_date);

            const installmentsForSale = allInstallments.filter(i => i.sale_id === sale.id);
            const installmentReceivable = installmentsForSale
                .filter(i => i.status !== 'cancelled' && i.status !== 'paid')
                .reduce((sum, inst) => sum + (inst.amount - inst.paid_amount), 0);

            const sisa_piutang = sale.status_penjualan === 'cancelled' || sale.status_penjualan === 'paid_off' ? 0 : sale.dp_sisa + installmentReceivable;

            // Billing Status Logic
            let billingStatus: BillingStatus = 'default';
            if (sale.status_penjualan === 'active') {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const sevenDaysFromNow = new Date();
                sevenDaysFromNow.setDate(today.getDate() + 7);

                const hasOverdue = installmentsForSale.some(i => i.status === 'overdue' || (new Date(i.due_date) < today && i.status !== 'paid' && i.status !== 'cancelled'));
                if (hasOverdue || sale.dp_sisa > 0) {
                    billingStatus = 'urgent';
                } else {
                    const hasUpcoming = installmentsForSale.some(i =>
                        (i.status === 'unpaid' || i.status === 'partial') &&
                        new Date(i.due_date) < sevenDaysFromNow &&
                        new Date(i.due_date) >= today
                    );
                    if (hasUpcoming) {
                        billingStatus = 'attention';
                    } else {
                        billingStatus = 'safe';
                    }
                }
            } else if (sale.status_penjualan === 'paid_off') {
                billingStatus = 'safe';
            }

            return {
                ...sale,
                lotInfo: lot ? `${project?.name} / ${lot.block}-${lot.lot_number}` : 'N/A',
                customerName: customer?.name || 'N/A',
                salesmanName: salesman?.name || 'N/A',
                bookingDate: sale.invoice_date,
                estimatedCompletionDate: estimatedCompletionDate.toISOString(),
                paymentMethodName: paymentMethod?.name || 'N/A',
                sisa_piutang: sisa_piutang,
                billingStatus: billingStatus
            };
        })
        .sort((a, b) => new Date(b.invoice_date).getTime() - new Date(a.invoice_date).getTime());
  });


  // --- New Dashboard Logic ---

  activeFilter = signal<DateRangePreset>('this_year');
  customDateRange = signal<{ start: string; end: string }>({ start: '', end: '' });
  compareEnabled = signal(false);

  setActiveFilter(preset: DateRangePreset) {
    this.activeFilter.set(preset);
    if (preset === 'all_time') {
      this.setCompareEnabled(false);
    }
  }
  setCustomDateRange(start: string, end: string) {
    this.customDateRange.set({ start, end });
  }
  setCompareEnabled(enabled: boolean) {
    this.compareEnabled.set(enabled);
  }

  private getDateRange = computed(() => {
    const preset = this.activeFilter();
    const custom = this.customDateRange();
    const now = new Date();
    let start = new Date();
    let end = new Date();

    const setTimeToStart = (date: Date) => date.setHours(0, 0, 0, 0);
    const setTimeToEnd = (date: Date) => date.setHours(23, 59, 59, 999);

    switch (preset) {
      case 'this_week':
        const firstDayOfWeek = now.getDate() - (now.getDay() === 0 ? 6 : now.getDay() - 1); // Monday as first day
        start = new Date(now.setDate(firstDayOfWeek));
        end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 6);
        break;
      case 'this_month':
        start = new Date(now.getFullYear(), now.getMonth(), 1);
        end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        break;
      case 'this_year':
        start = new Date(now.getFullYear(), 0, 1);
        end = new Date(now.getFullYear(), 11, 31);
        break;
      case 'last_year':
        const lastYear = now.getFullYear() - 1;
        start = new Date(lastYear, 0, 1);
        end = new Date(lastYear, 11, 31);
        break;
      case 'all_time':
        const allSaleDates = this.sales().map(s => new Date(s.invoice_date));
        if (allSaleDates.length > 0) {
          start = new Date(Math.min.apply(null, allSaleDates as any));
          end = new Date(Math.max.apply(null, allSaleDates as any));
        } else {
          start = new Date();
          end = new Date();
        }
        break;
      case 'custom':
        if (custom.start && custom.end) {
          start = new Date(custom.start);
          end = new Date(custom.end);
        }
        break;
    }

    setTimeToStart(start);
    setTimeToEnd(end);

    return { start, end };
  });

  private getPreviousDateRange = computed(() => {
    const current = this.getDateRange();
    let pStart = new Date(current.start);
    let pEnd = new Date(current.end);
    const duration = current.end.getTime() - current.start.getTime();

    pStart = new Date(current.start.getTime() - duration - 1);
    pEnd = new Date(current.start.getTime() - 1);

    pEnd.setHours(23, 59, 59, 999);

    return { start: pStart, end: pEnd };
  });

  private formatDaysLate(totalDays: number): string {
    const days = Math.max(0, Math.round(totalDays));
    if (days === 0) {
      return '0 hari';
    }

    const parts: string[] = [];
    let remainingDays = days;

    const years = Math.floor(remainingDays / 365);
    if (years > 0) {
      parts.push(`${years} tahun`);
      remainingDays %= 365;
    }

    const months = Math.floor(remainingDays / 30);
    if (months > 0) {
      parts.push(`${months} bulan`);
      remainingDays %= 30;
    }

    const weeks = Math.floor(remainingDays / 7);
    if (weeks > 0) {
      parts.push(`${weeks} minggu`);
      remainingDays %= 7;
    }

    if (remainingDays > 0) {
      parts.push(`${remainingDays} hari`);
    }

    return parts.join(' ');
  }

  urgentBillings = computed(() => {
    const activeSales = this.sales().filter(s => s.status_penjualan === 'active');
    const allInstallments = this.installments();
    const today = new Date();

    const billings = activeSales.map(sale => {
      const overdueInstallments = allInstallments.filter(i => {
        if (i.sale_id !== sale.id) return false;
        if (!i.due_date) return false;
        const isPastDue = new Date(i.due_date).getTime() < today.getTime();
        return i.status === 'overdue' || (i.status === 'unpaid' && isPastDue);
      });

      if (overdueInstallments.length === 0) {
        return null;
      }

      const totalOverdueAmount = overdueInstallments.reduce((sum, i) => {
        const paid = i.paid_amount ?? 0;
        const due = i.amount ?? 0;
        return sum + Math.max(0, due - paid);
      }, 0);

      const oldestDueDate = new Date(Math.min(...overdueInstallments.map(i => new Date(i.due_date!).getTime())));
      const daysLate = Math.floor((today.getTime() - oldestDueDate.getTime()) / (1000 * 3600 * 24));
      const customer = this.getCustomerById(sale.customer_id);
      const daysLateSanitized = daysLate > 0 ? daysLate : 0;

      return {
        saleId: sale.id,
        customerName: customer?.name || 'N/A',
        totalOverdueAmount,
        daysLate: daysLateSanitized,
        daysLateFormatted: this.formatDaysLate(daysLateSanitized)
      };
    }).filter(item => item !== null);

    return (billings as NonNullable<typeof billings[0]>[]).sort((a, b) => b.daysLate - a.daysLate);
  });

  private _getAggregatedSalesTrend(
    salesInPeriod: Sale[],
    dateRange: { start: Date; end: Date },
    preset: DateRangePreset
  ): { label: string; value: number; count: number }[] {
    type AggregationGranularity = 'day_of_week' | 'week_of_month' | 'month' | 'day' | 'week_of_year' | 'quarter';
    let granularity: AggregationGranularity;

    const oneDay = 1000 * 60 * 60 * 24;
    const durationDays = Math.round(Math.abs((dateRange.end.getTime() - dateRange.start.getTime()) / oneDay)) + 1;

    if (preset === 'all_time') {
        granularity = 'quarter';
    } else if (preset === 'this_week') {
        granularity = 'day_of_week';
    } else if (preset === 'this_month') {
        granularity = 'week_of_month';
    } else if (preset === 'this_year' || preset === 'last_year') {
        granularity = 'month';
    } else { // Custom
        if (durationDays <= 31) {
            granularity = 'day';
        } else if (durationDays <= 90) {
            granularity = 'week_of_year';
        } else if (durationDays <= 730) {
            granularity = 'month';
        } else {
            granularity = 'quarter';
        }
    }

    const aggregatedMap = new Map<string, { value: number; count: number; order: number }>();

    const getWeekOfYear = (d: Date): number => {
        const date = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
        date.setUTCDate(date.getUTCDate() + 4 - (date.getUTCDay() || 7));
        const yearStart = new Date(Date.UTC(date.getUTCFullYear(), 0, 1));
        const weekNo = Math.ceil((((date.getTime() - yearStart.getTime()) / 86400000) + 1) / 7);
        return weekNo;
    };

    const dayNames = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];
    const monthNamesShort = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Ags", "Sep", "Okt", "Nov", "Des"];

    const updateMap = (key: string, sale: Sale, order: number) => {
        if (!aggregatedMap.has(key)) {
            aggregatedMap.set(key, { value: 0, count: 0, order });
        }
        const entry = aggregatedMap.get(key)!;
        entry.value += sale.grand_total;
        entry.count += 1;
    };

    salesInPeriod.forEach(sale => {
        const saleDate = new Date(sale.invoice_date);
        let key: string;
        let order: number;

        switch (granularity) {
            case 'day_of_week':
                order = saleDate.getDay() === 0 ? 7 : saleDate.getDay();
                key = dayNames[saleDate.getDay()];
                updateMap(key, sale, order);
                break;
            case 'week_of_month':
                order = Math.ceil(saleDate.getDate() / 7);
                key = `W${order}`;
                updateMap(key, sale, order);
                break;
            case 'month':
                order = saleDate.getFullYear() * 100 + saleDate.getMonth();
                key = `${monthNamesShort[saleDate.getMonth()]} '${String(saleDate.getFullYear()).slice(-2)}`;
                updateMap(key, sale, order);
                break;
            case 'day':
                order = saleDate.getTime();
                key = `${String(saleDate.getDate()).padStart(2, '0')}/${String(saleDate.getMonth() + 1).padStart(2, '0')}`;
                updateMap(key, sale, order);
                break;
            case 'week_of_year':
                order = saleDate.getFullYear() * 100 + getWeekOfYear(saleDate);
                key = `W${getWeekOfYear(saleDate)} '${String(saleDate.getFullYear()).slice(-2)}`;
                updateMap(key, sale, order);
                break;
            case 'quarter':
                const quarter = Math.floor(saleDate.getMonth() / 3) + 1;
                order = saleDate.getFullYear() * 10 + quarter;
                key = `Q${quarter} '${String(saleDate.getFullYear()).slice(-2)}`;
                updateMap(key, sale, order);
                break;
        }
    });

    const resultWithEmptyBuckets = new Map(aggregatedMap);

    const populateEmpty = (key: string, order: number) => {
        if (!resultWithEmptyBuckets.has(key)) {
            resultWithEmptyBuckets.set(key, { value: 0, count: 0, order });
        }
    };

    switch (granularity) {
        case 'day_of_week':
            ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'].forEach((day, i) => populateEmpty(day, i + 1));
            break;
        case 'week_of_month':
            for (let i = 1; i <= 5; i++) populateEmpty(`W${i}`, i);
            break;
        case 'month':
             if (preset === 'this_year' || preset === 'last_year') {
                const year = dateRange.start.getFullYear();
                monthNamesShort.forEach((month, i) => {
                    populateEmpty(`${month} '${String(year).slice(-2)}`, year * 100 + i);
                });
             }
            break;
    }

    const finalResult = Array.from(resultWithEmptyBuckets.entries())
        .map(([label, data]) => ({ label, ...data }))
        .sort((a, b) => a.order - b.order);

     if (granularity === 'month' && preset !== 'this_year' && preset !== 'last_year') {
        return finalResult.filter(d => d.value > 0 || d.count > 0);
     }

    if(granularity === 'week_of_month') {
        const lastDay = new Date(dateRange.start.getFullYear(), dateRange.start.getMonth() + 1, 0);
        const numWeeks = Math.ceil(lastDay.getDate() / 7);
        return finalResult.slice(0, numWeeks);
    }

    return finalResult.map(({ label, value, count }) => ({ label, value, count }));
  }

  dashboardData = computed(() => {
    const range = this.getDateRange();
    const compare = this.compareEnabled();
    const prevRange = this.getPreviousDateRange();
    const preset = this.activeFilter();

    const calculateDataForRange = (dateRange: { start: Date; end: Date }, filterPreset: DateRangePreset) => {
        const salesInPeriod = this.sales().filter(s => {
            const saleDate = new Date(s.invoice_date);
            return saleDate >= dateRange.start && saleDate <= dateRange.end && s.status_penjualan !== 'cancelled';
        });

        const paymentsInPeriod = this.payments().filter(p => {
          const paymentDate = new Date(p.payment_date);
          return paymentDate >= dateRange.start && paymentDate <= dateRange.end;
        });

        const dpPaymentsValue = paymentsInPeriod.filter(p => p.payment_for.toLowerCase().includes('uang muka')).reduce((sum, p) => sum + p.amount, 0);

        const penerimaan = {
            value: paymentsInPeriod.reduce((sum, p) => sum + p.amount, 0),
            count: paymentsInPeriod.length,
        };

        const totalDP = {
            value: dpPaymentsValue,
            count: paymentsInPeriod.filter(p => p.payment_for.toLowerCase().includes('uang muka')).length,
        };

        const totalSales = {
            value: salesInPeriod.reduce((sum, s) => sum + s.grand_total, 0),
            count: salesInPeriod.length,
        };

        const salesByProjectByValue = this.projects().map(p => ({
            name: p.name,
            value: salesInPeriod.filter(s => this.getLotById(s.kavling_id)?.project_id === p.id)
                        .reduce((acc, cur) => acc + cur.grand_total, 0)
        })).filter(p => p.value > 0);

        const salesByProjectByUnit = this.projects().map(p => ({
            name: p.name,
            value: salesInPeriod.filter(s => this.getLotById(s.kavling_id)?.project_id === p.id).length
        })).filter(p => p.value > 0);

        const salesBySalesmanByValue = this.salesmen().map(sm => ({
            name: sm.name,
            value: salesInPeriod.filter(s => s.sales_id === sm.id)
                        .reduce((acc, cur) => acc + cur.grand_total, 0)
        })).filter(p => p.value > 0);

        const salesBySalesmanByUnit = this.salesmen().map(sm => ({
            name: sm.name,
            value: salesInPeriod.filter(s => s.sales_id === sm.id).length
        })).filter(p => p.value > 0);

        const salesTrend = this._getAggregatedSalesTrend(salesInPeriod, dateRange, filterPreset);

        return { penerimaan, totalDP, totalSales, salesByProject: { byValue: salesByProjectByValue, byUnit: salesByProjectByUnit }, salesBySalesman: { byValue: salesBySalesmanByValue, byUnit: salesBySalesmanByUnit }, salesTrend };
    };

    const currentData = calculateDataForRange(range, preset);
    const previousData = compare ? calculateDataForRange(prevRange, preset) : null;

    const activeSales = this.sales().filter(s => s.status_penjualan === 'active');

    // --- Receivables Breakdown Logic ---
    const allInstallments = this.installments();
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const sevenDaysFromNow = new Date();
    sevenDaysFromNow.setDate(today.getDate() + 7);

    let overdueAmount = 0;
    let attentionAmount = 0;
    let safeAmount = 0;

    // 1. Calculate DP receivables (all are considered 'overdue' if unpaid)
    overdueAmount += activeSales.reduce((sum, s) => sum + s.dp_sisa, 0);

    // 2. Categorize installment receivables for active sales
    const activeSaleIds = new Set(activeSales.map(s => s.id));
    allInstallments
      .filter(i => activeSaleIds.has(i.sale_id) && ['unpaid', 'partial', 'overdue'].includes(i.status))
      .forEach(inst => {
        const remaining = inst.amount - inst.paid_amount;
        const dueDate = new Date(inst.due_date);
        dueDate.setHours(0, 0, 0, 0);

        if (inst.status === 'overdue' || dueDate < today) {
          overdueAmount += remaining;
        } else if (dueDate >= today && dueDate < sevenDaysFromNow) {
          attentionAmount += remaining;
        } else {
          safeAmount += remaining;
        }
      });

    const totalRecvValue = overdueAmount + attentionAmount + safeAmount;

    const totalReceivables = {
        value: totalRecvValue,
        count: activeSales.length
    };

    const receivablesBreakdown = {
        overdue: {
            value: overdueAmount,
            percentage: totalRecvValue > 0 ? (overdueAmount / totalRecvValue) * 100 : 0
        },
        attention: {
            value: attentionAmount,
            percentage: totalRecvValue > 0 ? (attentionAmount / totalRecvValue) * 100 : 0
        },
        safe: {
            value: safeAmount,
            percentage: totalRecvValue > 0 ? (safeAmount / totalRecvValue) * 100 : 0
        }
    };

    // --- Inventory Logic ---
    const availableLots = this.lots().filter(l => l.status === 'available');
    const plotInventory = {
        value: availableLots.reduce((sum, l) => sum + l.base_price, 0),
        count: availableLots.length
    };

    const plotInventoryByProjectByValue = this.projects().map(p => ({
        name: p.name,
        value: this.lots()
            .filter(l => l.project_id === p.id && l.status === 'available')
            .reduce((sum, l) => sum + l.base_price, 0)
    })).filter(p => p.value > 0);

    const plotInventoryByProjectByUnit = this.projects().map(p => ({
        name: p.name,
        value: this.lots()
            .filter(l => l.project_id === p.id && l.status === 'available')
            .length
    })).filter(p => p.value > 0);

    return {
        current: currentData,
        previous: previousData,
        totalReceivables,
        receivablesBreakdown,
        plotInventory,
        plotInventoryByProject: {
            byValue: plotInventoryByProjectByValue,
            byUnit: plotInventoryByProjectByUnit
        }
    };
  });


  // Actions
  updateCompanyProfile(profile: CompanyProfile) {
    this.companyProfile.set(profile);
  }

  private _generateFormattedNumber(format: string, sequence: number, date: Date = new Date()): string {
    const now = date;
    const year = now.getFullYear().toString();
    const month = (now.getMonth() + 1).toString().padStart(2, '0');
    const day = now.getDate().toString().padStart(2, '0');

    const sequencePlaceholder = format.match(/\{[#NnOo]+\}/i)?.[0] || '{####}';
    const padding = sequencePlaceholder.length - 2;
    const sequenceStr = sequence.toString().padStart(padding, '0');

    return format
        .replace(/\{YYYY\}/gi, year)
        .replace(/\{YY\}/gi, year.slice(-2))
        .replace(/\{MM\}/g, month)
        .replace(/\{DD\}/gi, day)
        .replace(sequencePlaceholder, sequenceStr);
  }

  createSale(newSaleData: Omit<Sale, 'id' | 'invoice_no' | 'invoice_date' | 'dp_sisa' | 'status_dp' | 'status_followup' | 'dp_terbayar' | 'status_penjualan'>) {
    const lot = this.getLotById(newSaleData.kavling_id);
    if(lot?.status === 'sold') {
        alert(`Error: Kavling ${lot.block}/${lot.lot_number} sudah terjual.`);
        return;
    }

    const newId = this.sales().length > 0 ? Math.max(...this.sales().map(s => s.id)) + 1 : 1;
    const now = new Date();
    const invoiceFormat = this.companyProfile().nomor_format.faktur;
    const invoice_no = this._generateFormattedNumber(invoiceFormat, newId, now);

    const newSale: Sale = {
      ...newSaleData,
      id: newId,
      invoice_date: now.toISOString(),
      invoice_no: invoice_no,
      dp_terbayar: 0,
      dp_sisa: newSaleData.uang_muka_rp,
      status_dp: 'unpaid',
      status_penjualan: 'active',
      status_followup: 'normal'
    };

    this.sales.update(sales => [...sales, newSale]);

    this.lots.update(lots => lots.map(lot => lot.id === newSale.kavling_id ? {...lot, status: 'sold'} : lot));

    const principal = newSale.harga_netto - newSale.uang_muka_rp;
    const monthlyInstallment = newSale.tenor > 0 ? principal / newSale.tenor : 0;
    const saleDate = new Date(newSale.invoice_date);
    const newInstallments: Installment[] = [];

    if (newSale.tenor > 0) {
      for (let i = 1; i <= newSale.tenor; i++) {
          const dueDate = new Date(saleDate.getFullYear(), saleDate.getMonth() + i, newSale.jatuh_tempo_hari);
          const nextInstallmentId = this.installments().length > 0 ? Math.max(...this.installments().map(i => i.id)) + 1 + newInstallments.length : 1 + newInstallments.length;
          newInstallments.push({
              id: nextInstallmentId,
              sale_id: newId,
              installment_number: i,
              due_date: dueDate.toISOString(),
              amount: monthlyInstallment,
              paid_amount: 0,
              status: 'unpaid'
          });
      }
      this.installments.update(installments => [...installments, ...newInstallments]);
    }
  }

  updateSale(updatedSaleData: Sale): { success: boolean, message?: string } {
    const originalSale = this.getSaleById(updatedSaleData.id);
    if (!originalSale) {
        return { success: false, message: 'Sale not found.' };
    }

    const financialFieldsChanged = originalSale.harga_netto !== updatedSaleData.harga_netto ||
                                   originalSale.uang_muka_rp !== updatedSaleData.uang_muka_rp ||
                                   originalSale.tenor !== updatedSaleData.tenor ||
                                   originalSale.jatuh_tempo_hari !== updatedSaleData.jatuh_tempo_hari;

    if (financialFieldsChanged) {
        const hasPayments = this.getInstallmentsForSale(updatedSaleData.id).some(i => i.paid_amount > 0);
        if (hasPayments) {
            return { success: false, message: 'Tidak dapat mengubah detail finansial (harga, DP, tenor) karena sudah ada pembayaran angsuran yang tercatat. Untuk perubahan besar seperti ini, batalkan penjualan ini dan buat yang baru.' };
        }

        // Delete old installments and create new ones
        this.installments.update(insts => insts.filter(i => i.sale_id !== updatedSaleData.id));
        const principal = updatedSaleData.harga_netto - updatedSaleData.uang_muka_rp;
        const monthlyInstallment = updatedSaleData.tenor > 0 ? principal / updatedSaleData.tenor : 0;
        const saleDate = new Date(updatedSaleData.invoice_date);
        const newInstallments: Installment[] = [];

        if (updatedSaleData.tenor > 0) {
            for (let i = 1; i <= updatedSaleData.tenor; i++) {
                const dueDate = new Date(saleDate.getFullYear(), saleDate.getMonth() + i, updatedSaleData.jatuh_tempo_hari);
                const nextInstallmentId = (this.installments().length > 0 ? Math.max(...this.installments().map(i => i.id)) : 0) + 1 + newInstallments.length;
                newInstallments.push({
                    id: nextInstallmentId,
                    sale_id: updatedSaleData.id,
                    installment_number: i,
                    due_date: dueDate.toISOString(),
                    amount: monthlyInstallment,
                    paid_amount: 0,
                    status: 'unpaid'
                });
            }
            this.installments.update(installments => [...installments, ...newInstallments]);
        }
    }

    // Update the sale object itself
    this.sales.update(sales => sales.map(s => s.id === updatedSaleData.id ? updatedSaleData : s));

    // Check if sale status needs to be updated (e.g., to paid_off)
    this.checkAndSetSalePaidOff(updatedSaleData.id);

    return { success: true };
  }

  payDP(saleId: number, amount: number) {
    this.sales.update(sales => sales.map(s => {
      if (s.id === saleId) {
        const newDPTerbayar = s.dp_terbayar + amount;
        return {
          ...s,
          dp_terbayar: newDPTerbayar,
          dp_sisa: s.uang_muka_rp - newDPTerbayar,
          status_dp: newDPTerbayar >= s.uang_muka_rp ? 'paid' : 'unpaid'
        };
      }
      return s;
    }));

    const newPaymentId = this.payments().length > 0 ? Math.max(...this.payments().map(p => p.id)) + 1 : 1;
    const receiptFormat = this.companyProfile().nomor_format.kuitansi;
    const receipt_no = this._generateFormattedNumber(receiptFormat, newPaymentId);

    const newPayment: Payment = {
        id: newPaymentId,
        receipt_no,
        sale_id: saleId,
        payment_date: new Date().toISOString(),
        amount: amount,
        payment_for: `Uang Muka (DP)`
    };
    this.payments.update(payments => [...payments, newPayment]);

    this.checkAndSetSalePaidOff(saleId);
  }

  payInstallment(installmentId: number, amount: number) {
    let saleIdToUpdate: number | null = null;
    let installmentNumber: number | null = null;

    this.installments.update(installments =>
      installments.map(i => {
        if (i.id === installmentId) {
          saleIdToUpdate = i.sale_id;
          installmentNumber = i.installment_number;
          const newPaidAmount = i.paid_amount + amount;
          const isPaid = newPaidAmount >= i.amount;
          return {
            ...i,
            paid_amount: newPaidAmount,
            status: isPaid ? 'paid' : 'partial',
            payment_date: new Date().toISOString()
          };
        }
        return i;
      })
    );
    if(saleIdToUpdate) {
      const newPaymentId = this.payments().length > 0 ? Math.max(...this.payments().map(p => p.id)) + 1 : 1;
      const receiptFormat = this.companyProfile().nomor_format.kuitansi;
      const receipt_no = this._generateFormattedNumber(receiptFormat, newPaymentId);

      const newPayment: Payment = {
          id: newPaymentId,
          receipt_no,
          sale_id: saleIdToUpdate,
          payment_date: new Date().toISOString(),
          amount: amount,
          payment_for: `Angsuran ke-${installmentNumber}`
      };
      this.payments.update(payments => [...payments, newPayment]);
      this.checkAndSetSalePaidOff(saleIdToUpdate);
    }
  }

  makeFlexiblePayment(saleId: number, amount: number, paymentDate: string) {
    let sale = this.getSaleById(saleId);
    if (!sale) return;

    let remainingAmountToAllocate = amount;
    const paymentTimestamp = new Date(paymentDate).toISOString();

    // Create one payment record for this entire transaction
    const newPaymentId = this.payments().length > 0 ? Math.max(...this.payments().map(p => p.id)) + 1 : 1;
    const receiptFormat = this.companyProfile().nomor_format.kuitansi;
    const receipt_no = this._generateFormattedNumber(receiptFormat, newPaymentId, new Date(paymentDate));

    const newPayment: Payment = {
        id: newPaymentId,
        receipt_no,
        sale_id: saleId,
        payment_date: paymentTimestamp,
        amount: amount,
        payment_for: `Pembayaran Fleksibel`
    };
    this.payments.update(payments => [...payments, newPayment]);

    // 1. Allocate to DP first if there is a remaining balance
    if (sale.dp_sisa > 0) {
        const dpPayment = Math.min(remainingAmountToAllocate, sale.dp_sisa);
        this.sales.update(sales => sales.map(s => s.id === saleId ? {
            ...s,
            dp_terbayar: s.dp_terbayar + dpPayment,
            dp_sisa: s.dp_sisa - dpPayment,
            status_dp: (s.dp_terbayar + dpPayment) >= s.uang_muka_rp ? 'paid' : 'unpaid'
        } : s));
        remainingAmountToAllocate -= dpPayment;
    }

    if (remainingAmountToAllocate <= 0) {
        this.checkAndSetSalePaidOff(saleId);
        return;
    }

    // 2. Allocate to installments
    const installmentsToPay = this.getInstallmentsForSale(saleId)
        .filter(i => ['unpaid', 'partial', 'overdue'].includes(i.status))
        .sort((a, b) => new Date(a.due_date).getTime() - new Date(b.due_date).getTime());

    const updatedInstallments = [...this.installments()];

    for (const inst of installmentsToPay) {
        if (remainingAmountToAllocate <= 0) break;

        const index = updatedInstallments.findIndex(i => i.id === inst.id);
        if (index === -1) continue;

        const needed = inst.amount - inst.paid_amount;
        const paymentForThisInstallment = Math.min(remainingAmountToAllocate, needed);

        const newPaidAmount = inst.paid_amount + paymentForThisInstallment;
        const isPaid = newPaidAmount >= inst.amount;

        updatedInstallments[index] = {
            ...updatedInstallments[index],
            paid_amount: newPaidAmount,
            status: isPaid ? 'paid' : 'partial',
            payment_date: paymentTimestamp,
        };

        remainingAmountToAllocate -= paymentForThisInstallment;
    }

    this.installments.set(updatedInstallments);
    this.checkAndSetSalePaidOff(saleId);
  }

  private checkAndSetSalePaidOff(saleId: number) {
    const sale = this.getSaleById(saleId);
    const saleInstallments = this.getInstallmentsForSale(saleId);
    if (sale && sale.dp_sisa <= 0 && saleInstallments.every(i => i.status === 'paid' || i.status === 'cancelled')) {
      this.sales.update(sales => sales.map(s => s.id === saleId ? {...s, status_penjualan: 'paid_off'} : s));
    }
  }

  cancelSale(saleId: number) {
    let lotIdToUpdate: number | null = null;
    this.sales.update(sales => sales.map(s => {
      if (s.id === saleId) {
        lotIdToUpdate = s.kavling_id;
        return { ...s, status_penjualan: 'cancelled' };
      }
      return s;
    }));

    if (lotIdToUpdate) {
      this.lots.update(lots => lots.map(lot =>
        lot.id === lotIdToUpdate ? { ...lot, status: 'available' } : lot
      ));
      this.installments.update(installments =>
        installments.map(i =>
          i.sale_id === saleId ? { ...i, status: 'cancelled' } : i
        )
      );
    }
  }

  // --- Master Data CRUD ---

  addProject(projectData: Omit<Project, 'id'>) {
    const newId = this.projects().length > 0 ? Math.max(...this.projects().map(p => p.id)) + 1 : 1;
    const newProject: Project = { id: newId, ...projectData };
    this.projects.update(projects => [...projects, newProject]);
  }

  updateProject(updatedProject: Project) {
    this.projects.update(projects => projects.map(p => p.id === updatedProject.id ? updatedProject : p));
  }

  deleteProject(id: number): { success: boolean; message?: string } {
    const hasLots = this.lots().some(l => l.project_id === id);
    if (hasLots) {
      return { success: false, message: 'Project cannot be deleted. It has lots assigned to it. Please remove or reassign the lots first.' };
    }
    this.projects.update(projects => projects.filter(p => p.id !== id));
    return { success: true };
  }

  addLot(lotData: Omit<Lot, 'id' | 'status'>) {
    const newId = this.lots().length > 0 ? Math.max(...this.lots().map(l => l.id)) + 1 : 1;
    const newLot: Lot = { id: newId, status: 'available', ...lotData };
    this.lots.update(lots => [...lots, newLot]);
  }

  updateLot(updatedLot: Lot) {
    this.lots.update(lots => lots.map(l => l.id === updatedLot.id ? updatedLot : l));
  }

  deleteLot(id: number): { success: boolean; message?: string } {
    const lotToDelete = this.getLotById(id);
    if (lotToDelete?.status === 'sold') {
      return { success: false, message: 'Cannot delete a lot that has been sold. The sale must be cancelled first to make the lot available.' };
    }
    this.lots.update(lots => lots.filter(l => l.id !== id));
    return { success: true };
  }

  addCustomer(customerData: Omit<Customer, 'id'>) {
    const newId = this.customers().length > 0 ? Math.max(...this.customers().map(c => c.id)) + 1 : 1;
    const newCustomer: Customer = { id: newId, ...customerData };
    this.customers.update(customers => [...customers, newCustomer]);
  }

  updateCustomer(updatedCustomer: Customer) {
    this.customers.update(customers => customers.map(c => c.id === updatedCustomer.id ? updatedCustomer : c));
  }

  deleteCustomer(id: number): { success: boolean; message?: string } {
    const sale = this.sales().find(s => s.customer_id === id && s.status_penjualan !== 'cancelled');
    if (sale) {
      return { success: false, message: `Cannot delete customer. They are associated with an active sale (Invoice: ${sale.invoice_no}).` };
    }
    this.customers.update(customers => customers.filter(c => c.id !== id));
    return { success: true };
  }

  addSalesman(salesmanData: Omit<Salesman, 'id'>) {
    const newId = this.salesmen().length > 0 ? Math.max(...this.salesmen().map(s => s.id)) + 1 : 1;
    const newSalesman: Salesman = { id: newId, ...salesmanData };
    this.salesmen.update(salesmen => [...salesmen, newSalesman]);
  }

  updateSalesman(updatedSalesman: Salesman) {
    this.salesmen.update(salesmen => salesmen.map(s => s.id === updatedSalesman.id ? updatedSalesman : s));
  }

  deleteSalesman(id: number): { success: boolean; message?: string } {
    const sale = this.sales().find(s => s.sales_id === id && s.status_penjualan !== 'cancelled');
    if (sale) {
      return { success: false, message: `Cannot delete salesman. They are associated with an active sale (Invoice: ${sale.invoice_no}).` };
    }
    this.salesmen.update(salesmen => salesmen.filter(s => s.id !== id));
    return { success: true };
  }

  // --- Data Management ---
  exportDataAsJson() {
    const appData: AppData = this.appData();
    const jsonString = JSON.stringify(appData, null, 2);
    const blob = new Blob([jsonString], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `kavling-pro-backup-${new Date().toISOString().slice(0, 10)}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  importDataFromJson(jsonString: string) {
    try {
      const data: AppData = JSON.parse(jsonString);

      if (
        !data.companyProfile || !data.projects || !data.lots ||
        !data.customers || !data.salesmen || !data.sales || !data.installments
      ) {
        throw new Error('Invalid JSON format. Missing required keys.');
      }

      this.companyProfile.set(data.companyProfile);
      this.projects.set(data.projects);
      this.lots.set(data.lots);
      this.customers.set(data.customers);
      this.salesmen.set(data.salesmen);
      this.sales.set(data.sales);
      this.installments.set(data.installments);
      this.payments.set(data.payments || []); // Handle imports from older versions
      this.initialSetupDone.set(true);

      alert('Data imported successfully!');

    } catch (error) {
      console.error('Error importing data:', error);
      alert('Failed to import data. Please check the file format and console for errors.');
    }
  }

  resetAllData() {
    // Bersihkan penyimpanan lokal agar benar-benar kosong
    try {
      localStorage.removeItem(STORAGE_KEY);
      Object.keys(localStorage)
        .filter((k) => k.startsWith('forecast_'))
        .forEach((k) => localStorage.removeItem(k));
    } catch (e) {
      console.error('Gagal menghapus cache localStorage saat reset', e);
    }

    this.currentUser.set(DEFAULT_USER);
    this.companyProfile.set({
      nama: '',
      alamat: '',
      telepon: '',
      email: '',
      website: '',
      npwp: '',
      logo_url: '',
      ttd_admin_nama: '',
      footer_cetak: '',
      nomor_format: {
        faktur: '',
        kuitansi: '',
      },
    });
    this.projects.set([]);
    this.lots.set([]);
    this.customers.set([]);
    this.salesmen.set([]);
    this.sales.set([]);
    this.installments.set([]);
    this.payments.set([]);
    // Tandai setup selesai supaya effect menyimpan keadaan kosong
    this.initialSetupDone.set(true);
    this.scheduleSave();
  }

  historicalSalesMonthsCount = computed(() => {
    const salesHistory = this.sales()
      .filter(s => s.status_penjualan !== 'cancelled');

    if(salesHistory.length === 0) return 0;

    const monthlyDataKeys = new Set(salesHistory.map(sale => {
      const date = new Date(sale.invoice_date);
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
    }));

    return monthlyDataKeys.size;
  });

  async triggerForecast(preset: DateRangePreset) {
    this.forecastLoading.set("Menginisialisasi...");
    this.forecastError.set(null);
    this.salesForecast.set(null);
    this.forecastDescription.set(null);

    try {
      this.forecastLoading.set("Mengumpulkan data penjualan historis...");
      const salesHistory = this.sales()
        .filter(s => s.status_penjualan !== 'cancelled')
        .sort((a,b) => new Date(a.invoice_date).getTime() - new Date(b.invoice_date).getTime());

      if(salesHistory.length === 0) {
        throw new Error("Tidak ada data penjualan historis untuk membuat proyeksi.");
      }

      const monthlyData: { [key: string]: { total_penjualan: number, unit_terjual: number } } = {};
      salesHistory.forEach(sale => {
        const date = new Date(sale.invoice_date);
        const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        if (!monthlyData[monthKey]) {
          monthlyData[monthKey] = { total_penjualan: 0, unit_terjual: 0 };
        }
        monthlyData[monthKey].total_penjualan += sale.grand_total;
        monthlyData[monthKey].unit_terjual += 1;
      });

      const historyForAI = Object.entries(monthlyData).map(([bulan, data]) => ({ bulan, ...data }));

      if(historyForAI.length < 3) {
        throw new Error("Proyeksi memerlukan setidaknya 3 bulan data penjualan historis.");
      }

      let monthsToForecast: number;
      let granularity: 'bulanan' | 'kuartalan';
      let forecastDurationText: string;
      let schemaDescription: string;

      if (preset === 'this_year') {
          monthsToForecast = 12;
          granularity = 'bulanan';
          forecastDurationText = '12 bulan ke depan';
          schemaDescription = 'Bulan proyeksi format YYYY-MM';
      } else if (preset === 'all_time') {
          monthsToForecast = 36; // 12 quarters
          granularity = 'kuartalan';
          forecastDurationText = '12 kuartal ke depan (3 tahun)';
          schemaDescription = "Kuartal proyeksi format YYYY-Q# (e.g., 2026-Q1)";
      } else {
        throw new Error("Proyeksi tidak diizinkan untuk periode filter yang dipilih.");
      }

      const historyJson = JSON.stringify(historyForAI);
      const cacheKey = `forecast_${preset}_${granularity}_${monthsToForecast}_${historyJson}`;
      const cachedDataString = localStorage.getItem(cacheKey);

      if (cachedDataString) {
          console.log("Menggunakan proyeksi tersimpan (cache).");
          const cachedData = JSON.parse(cachedDataString);
          this.salesForecast.set(cachedData);
          this.forecastDescription.set(`Berdasarkan ${historyForAI.length} bulan data historis, berikut adalah proyeksi penjualan ${granularity} untuk ${forecastDurationText}.`);
          this.forecastLoading.set(false);
          return;
      }

      const forecast = await this.getSalesForecast(historyJson, monthsToForecast, granularity, forecastDurationText, schemaDescription);

      this.forecastLoading.set("Memproses hasil...");
      this.salesForecast.set(forecast);
      this.forecastDescription.set(`Berdasarkan ${historyForAI.length} bulan data historis, berikut adalah proyeksi penjualan ${granularity} untuk ${forecastDurationText}.`);
      localStorage.setItem(cacheKey, JSON.stringify(forecast));

    } catch(e: any) {
        console.error("Forecast Error:", e);
        this.forecastError.set(e.message || "Terjadi kesalahan saat menghitung proyeksi.");
    } finally {
        this.forecastLoading.set(false);
    }
  }

  private async getSalesForecast(
    historyData: string,
    months: number,
    granularity: 'bulanan' | 'kuartalan',
    forecastDurationText: string,
    schemaDescription: string
  ): Promise<ForecastData[]> {
    const history = JSON.parse(historyData) as Array<{ bulan: string; total_penjualan: number; unit_terjual: number }>;
    if (!Array.isArray(history) || history.length === 0) {
      throw new Error("Data historis tidak valid untuk proyeksi.");
    }

    // Urutkan berdasarkan bulan
    const sorted = [...history].sort((a, b) => a.bulan.localeCompare(b.bulan));
    const salesSeries = sorted.map((h) => h.total_penjualan || 0);
    const unitSeries = sorted.map((h) => h.unit_terjual || 0);

    const linearForecast = (series: number[], steps: number) => {
      const n = series.length;
      const meanX = (n - 1) / 2;
      const meanY = series.reduce((a, b) => a + b, 0) / n;
      let num = 0;
      let den = 0;
      series.forEach((y, i) => {
        num += (i - meanX) * (y - meanY);
        den += (i - meanX) ** 2;
      });
      const slope = den === 0 ? 0 : num / den;
      const intercept = meanY - slope * meanX;

      return Array.from({ length: steps }, (_, k) => {
        const x = n + k;
        const y = intercept + slope * x;
        return Math.max(0, y);
      });
    };

    const salesForecast = linearForecast(salesSeries, months);
    const unitForecast = linearForecast(unitSeries, months);

    const pad2 = (v: number) => String(v).padStart(2, '0');
    const futureLabels: string[] = [];
    const lastDateParts = sorted[sorted.length - 1].bulan.split('-');
    const lastDate = new Date(Number(lastDateParts[0]), Number(lastDateParts[1]) - 1, 1);

    for (let i = 1; i <= months; i++) {
      const d = new Date(lastDate);
      d.setMonth(d.getMonth() + i);
      if (granularity === 'kuartalan') {
        const quarter = Math.floor(d.getMonth() / 3) + 1;
        futureLabels.push(`${d.getFullYear()}-Q${quarter}`);
      } else {
        futureLabels.push(`${d.getFullYear()}-${pad2(d.getMonth() + 1)}`);
      }
    }

    return futureLabels.map((bulan, idx) => {
      const base = salesForecast[idx] ?? 0;
      const pessimistic = base * 0.9;
      const optimistic = base * 1.1;
      return {
        bulan,
        proyeksi_penjualan: Math.round(base),
        unit_terjual: Math.max(0, Math.round(unitForecast[idx] ?? 0)),
        proyeksi_penjualan_pesimis: Math.round(pessimistic),
        proyeksi_penjualan_optimis: Math.round(optimistic),
      } as ForecastData;
    });
  }
}
