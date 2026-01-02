import { Component, ChangeDetectionStrategy, inject, signal, computed, WritableSignal, effect, ElementRef } from '@angular/core';
import { CommonModule, CurrencyPipe, DatePipe } from '@angular/common';
import { DataService } from '../../services/data.service';
import { Sale, Lot, Customer, Salesman, SaleStatus, DPStatus } from '../../models/data.models';
import { SaleDetailComponent } from '../sale-detail/sale-detail.component';

@Component({
  selector: 'app-sales',
  standalone: true,
  imports: [CommonModule, CurrencyPipe, DatePipe, SaleDetailComponent],
  templateUrl: './sales.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SalesComponent {
  dataService = inject(DataService);
  elementRef = inject(ElementRef);
  showAddModal = signal(false);
  selectedSaleId = signal<number | null>(null);

  // --- Filter State Signals ---
  filterProjectName = signal('');
  filterCustomerName = signal('');
  filterStartDate = signal('');
  filterEndDate = signal('');
  filterPaymentMethod = signal('');
  filterDpStatus = signal('');
  filterSaleStatus = signal('');
  filterSalesmanId = signal('');
  filterBillingStatus = signal('');

  // --- Sort State Signals ---
  sortColumn = signal<string>('bookingDate');
  sortDirection = signal<'asc' | 'desc'>('desc');

  // --- Computed Signal for Filtered and Sorted Sales ---
  filteredSales = computed(() => {
    const allSales = this.dataService.soldLotsSummary();
    
    const projectName = this.filterProjectName().toLowerCase().trim();
    const customerName = this.filterCustomerName().toLowerCase().trim();
    const startDate = this.filterStartDate();
    const endDate = this.filterEndDate();
    const paymentMethod = this.filterPaymentMethod();
    const dpStatus = this.filterDpStatus();
    const saleStatus = this.filterSaleStatus();
    const salesmanId = this.filterSalesmanId();
    const billingStatus = this.filterBillingStatus();

    const filteredData = allSales.filter(sale => {
      const projectMatch = !projectName || sale.lotInfo.toLowerCase().includes(projectName);
      const customerMatch = !customerName || sale.customerName.toLowerCase().includes(customerName);
      const paymentMethodMatch = !paymentMethod || sale.metode_id === paymentMethod;
      const dpStatusMatch = !dpStatus || sale.status_dp === dpStatus;
      const saleStatusMatch = !saleStatus || sale.status_penjualan === saleStatus;
      const salesmanMatch = !salesmanId || sale.sales_id.toString() === salesmanId;
      const billingStatusMatch = !billingStatus || sale.billingStatus === billingStatus;

      let dateMatch = true;
      if (startDate && endDate) {
          const saleDate = new Date(sale.bookingDate);
          const end = new Date(endDate);
          end.setDate(end.getDate() + 1); // Make end date inclusive
          dateMatch = saleDate >= new Date(startDate) && saleDate < end;
      } else if (startDate) {
          dateMatch = new Date(sale.bookingDate) >= new Date(startDate);
      } else if (endDate) {
          const end = new Date(endDate);
          end.setDate(end.getDate() + 1); // Make end date inclusive
          dateMatch = new Date(sale.bookingDate) < end;
      }

      return projectMatch && customerMatch && paymentMethodMatch && dpStatusMatch && saleStatusMatch && salesmanMatch && dateMatch && billingStatusMatch;
    });

    // --- Sorting Logic ---
    const column = this.sortColumn();
    const direction = this.sortDirection();
    type SaleSummary = (typeof allSales)[0];

    return [...filteredData].sort((a: SaleSummary, b: SaleSummary) => {
        const valA = a[column as keyof SaleSummary];
        const valB = b[column as keyof SaleSummary];

        let comparison = 0;
        if (valA === null || typeof valA === 'undefined') comparison = -1;
        else if (valB === null || typeof valB === 'undefined') comparison = 1;
        else if (typeof valA === 'string' && typeof valB === 'string') {
            comparison = valA.localeCompare(valB);
        } else {
             if (valA > valB) comparison = 1;
             else if (valA < valB) comparison = -1;
        }
        
        return direction === 'asc' ? comparison : -comparison;
    });
  });

  // New Sale Form State
  newSaleLotId = signal<number | null>(null);
  newSaleCustomerId = signal<number | null>(null);
  newSaleSalesmanId = signal<number | null>(null);
  newSaleMetodeId = signal<string | null>(null);
  
  newSaleHargaDasar = signal(0);
  newSalePromoDiskon = signal(0);
  newSaleHargaNetto = computed(() => this.newSaleHargaDasar() - this.newSalePromoDiskon());

  newSaleUangMukaPersen = signal(20);
  newSaleUangMukaRp = computed(() => (this.newSaleHargaNetto() * this.newSaleUangMukaPersen()) / 100);

  newSaleBiayaPPJB = signal(0);
  newSaleBiayaSHM = signal(0);
  newSaleBiayaLain = signal(0);
  newSaleGrandTotal = computed(() => this.newSaleHargaNetto() + this.newSaleBiayaPPJB() + this.newSaleBiayaSHM() + this.newSaleBiayaLain());

  newSaleTenor = signal(12);
  newSaleJatuhTempoHari = signal(16);
  newSaleCatatan = signal('');

  newSaleEstimasiAngsuran = computed(() => {
    const tenor = this.newSaleTenor();
    if (tenor <= 0) {
      return 0;
    }
    const pokokPinjaman = this.newSaleHargaNetto() - this.newSaleUangMukaRp();
    if (pokokPinjaman <= 0) {
        return 0;
    }
    return pokokPinjaman / tenor;
  });

  constructor() {
    effect(() => {
        const lotId = this.newSaleLotId();
        if(lotId) {
            const selectedLot = this.dataService.getLotById(lotId);
            if(selectedLot) {
                this.newSaleHargaDasar.set(selectedLot.base_price);
            }
        }
    });

    // Handle 'Cash Keras' selection
    effect(() => {
      if (this.newSaleMetodeId() === 'MP02') { // 'MP02' is 'Cash Keras'
        this.newSaleTenor.set(0);
        this.newSaleUangMukaPersen.set(100);
      }
    });

    // Effect for handling navigation from dashboard
    effect(() => {
      const saleId = this.dataService.requestedSaleDetailId();
      if(saleId !== null) {
        this.selectSale(saleId);
        this.dataService.requestedSaleDetailId.set(null); // Reset after handling
      }
    });
  }
  
  availableLots = this.dataService.availableLots;
  customers = this.dataService.customers;
  salesmen = this.dataService.salesmen;
  paymentMethods = this.dataService.paymentMethods;

  selectSale(saleId: number) {
    this.selectedSaleId.set(saleId);
    const mainEl = (this.elementRef.nativeElement as HTMLElement).closest('main');
    if (mainEl) {
      mainEl.scrollTop = 0;
    }
  }

  closeDetail() {
    this.selectedSaleId.set(null);
  }

  public showSalesList(): void {
    this.selectedSaleId.set(null);
  }

  openAddModal() {
    this.resetForm();
    this.showAddModal.set(true);
  }

  closeAddModal() {
    this.showAddModal.set(false);
  }

  resetForm() {
    this.newSaleLotId.set(null);
    this.newSaleCustomerId.set(null);
    this.newSaleSalesmanId.set(null);
    this.newSaleMetodeId.set(null);
    this.newSaleHargaDasar.set(0);
    this.newSalePromoDiskon.set(0);
    this.newSaleUangMukaPersen.set(20);
    this.newSaleBiayaPPJB.set(0);
    this.newSaleBiayaSHM.set(0);
    this.newSaleBiayaLain.set(0);
    this.newSaleTenor.set(12);
    this.newSaleJatuhTempoHari.set(16);
    this.newSaleCatatan.set('');
  }

  onFormValueChange(signal: WritableSignal<any>, event: Event, isNumber: boolean = true) {
    const value = (event.target as HTMLInputElement).value;
    signal.set(isNumber ? parseInt(value, 10) || 0 : value);
  }
   onFormValueChangeString(signal: WritableSignal<any>, event: Event) {
    const value = (event.target as HTMLSelectElement | HTMLTextAreaElement).value;
    signal.set(value);
  }

  onSaveSale() {
    if (!this.newSaleLotId() || !this.newSaleCustomerId() || !this.newSaleSalesmanId() || !this.newSaleMetodeId()) {
      alert('Please fill all required fields.');
      return;
    }

    this.dataService.createSale({
        kavling_id: this.newSaleLotId()!,
        customer_id: this.newSaleCustomerId()!,
        sales_id: this.newSaleSalesmanId()!,
        metode_id: this.newSaleMetodeId()!,
        harga_dasar: this.newSaleHargaDasar(),
        promo_diskon: this.newSalePromoDiskon(),
        harga_netto: this.newSaleHargaNetto(),
        uang_muka_persen: this.newSaleUangMukaPersen(),
        uang_muka_rp: this.newSaleUangMukaRp(),
        biaya_ppjb: this.newSaleBiayaPPJB(),
        biaya_shm: this.newSaleBiayaSHM(),
        biaya_lain_total: this.newSaleBiayaLain(),
        grand_total: this.newSaleGrandTotal(),
        tenor: this.newSaleTenor(),
        jatuh_tempo_hari: this.newSaleJatuhTempoHari(),
        catatan: this.newSaleCatatan(),
    });
    this.closeAddModal();
  }

  // --- Filter Methods ---
  resetFilters() {
    this.filterProjectName.set('');
    this.filterCustomerName.set('');
    this.filterStartDate.set('');
    this.filterEndDate.set('');
    this.filterPaymentMethod.set('');
    this.filterDpStatus.set('');
    this.filterSaleStatus.set('');
    this.filterSalesmanId.set('');
    this.filterBillingStatus.set('');
  }

  onFilterChange(signal: WritableSignal<string>, event: Event) {
    const input = event.target as HTMLInputElement | HTMLSelectElement;
    signal.set(input.value);
  }

  // --- Sorting Method ---
  onSort(column: string) {
    if (this.sortColumn() === column) {
        this.sortDirection.update(dir => (dir === 'asc' ? 'desc' : 'asc'));
    } else {
        this.sortColumn.set(column);
        this.sortDirection.set('asc');
    }
  }

  // --- Styling Methods ---
  getSaleStatusClass(status: SaleStatus) {
    switch (status) {
      case 'active': return 'bg-blue-100 text-blue-800';
      case 'paid_off': return 'bg-green-100 text-green-800';
      case 'cancelled': return 'bg-red-100 text-red-800';
      case 'draft': return 'bg-gray-100 text-gray-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  }
  
  getDPStatusClass(status: DPStatus) {
    switch (status) {
        case 'paid': return 'bg-green-100 text-green-800';
        case 'unpaid': return 'bg-yellow-100 text-yellow-800';
        default: return 'bg-gray-100 text-gray-800';
    }
  }
}