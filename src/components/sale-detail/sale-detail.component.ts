import { Component, ChangeDetectionStrategy, computed, inject, input, output, signal } from '@angular/core';
import { CommonModule, CurrencyPipe, DatePipe } from '@angular/common';
import { DataService } from '../../services/data.service';
import { Installment, InstallmentStatus, Payment, Sale } from '../../models/data.models';
import { PrintReceiptComponent } from '../print-receipt/print-receipt.component';

@Component({
  selector: 'app-sale-detail',
  standalone: true,
  imports: [CommonModule, CurrencyPipe, DatePipe, PrintReceiptComponent],
  templateUrl: './sale-detail.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SaleDetailComponent {
  dataService = inject(DataService);
  saleId = input.required<number>();
  close = output();
  
  paymentModalInstallment = signal<Installment | null>(null);
  showDPPaymentModal = signal(false);
  dpPaymentAmount = signal(0);
  showCancelConfirmModal = signal(false);

  paymentToPrint = signal<any | null>(null);

  // --- Flexible Payment Form State ---
  flexiblePaymentAmount = signal(0);
  flexiblePaymentDate = signal(new Date().toISOString().split('T')[0]); // YYYY-MM-DD format

  // --- Edit Modal State ---
  showEditModal = signal(false);
  editableSale = signal<Sale | null>(null);
  customers = this.dataService.customers;
  salesmen = this.dataService.salesmen;
  paymentMethods = this.dataService.paymentMethods;


  sale = computed(() => this.dataService.sales().find(s => s.id === this.saleId()));
  
  saleDetails = computed(() => {
    const s = this.sale();
    if (!s) return null;
    return {
      ...s,
      lot: this.dataService.getLotById(s.kavling_id),
      customer: this.dataService.getCustomerById(s.customer_id),
      project: this.dataService.getProjectById(this.dataService.getLotById(s.kavling_id)?.project_id ?? -1),
      payment_method: this.dataService.paymentMethods().find(pm => pm.id === s.metode_id)
    };
  });

  installments = computed(() => this.dataService.getInstallmentsForSale(this.saleId()));
  payments = computed(() => this.dataService.getPaymentsForSale(this.saleId()));

  totalPaid = computed(() => {
    const s = this.sale();
    if (!s) return 0;
    const installmentPaid = this.installments().reduce((sum, i) => sum + i.paid_amount, 0);
    return s.dp_terbayar + installmentPaid;
  });

  totalReceivable = computed(() => {
    const s = this.sale();
    if (!s) return 0;
    return s.grand_total - this.totalPaid();
  });

  whatsAppLink = computed(() => {
    const details = this.saleDetails();
    // The button should only appear for active sales where customer data is complete.
    if (!details || !details.customer?.phone || details.status_penjualan !== 'active') {
      return '';
    }

    const customerName = details.customer.name;
    const companyName = this.dataService.companyProfile().nama;
    const lotCode = `${details.lot?.block}/${details.lot?.lot_number}`;
    
    let reminderMessage: string;

    // Case 1: DP is not fully paid. This is the first priority.
    if (details.status_dp === 'unpaid' && details.dp_sisa > 0) {
      const remainingDp = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(details.dp_sisa);
      reminderMessage = `Mengingatkan mengenai sisa pembayaran Uang Muka (DP) untuk kavling ${lotCode} sebesar ${remainingDp}.`;
    } 
    // Case 2: No DP issue, check for overdue installments.
    else {
      const overdueInstallment = this.installments().find(i => i.status === 'overdue');
      if (overdueInstallment) {
        const installmentNumber = overdueInstallment.installment_number;
        const dueDate = new Date(overdueInstallment.due_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        const remainingAmount = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(overdueInstallment.amount - overdueInstallment.paid_amount);
        reminderMessage = `Mengingatkan bahwa pembayaran angsuran ke-${installmentNumber} untuk kavling ${lotCode} telah melewati jatuh tempo (${dueDate}) dengan sisa tagihan sebesar ${remainingAmount}.`;
      }
      // Case 3: No overdue, check for the next upcoming/partial installment.
      else {
        const nextUnpaid = this.installments().find(i => ['unpaid', 'partial'].includes(i.status));
        if (nextUnpaid) {
            const installmentNumber = nextUnpaid.installment_number;
            const dueDate = new Date(nextUnpaid.due_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
            const remainingAmount = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(nextUnpaid.amount - nextUnpaid.paid_amount);
            reminderMessage = `Mengingatkan pembayaran angsuran ke-${installmentNumber} untuk kavling ${lotCode} akan jatuh tempo pada ${dueDate} dengan sisa tagihan sebesar ${remainingAmount}.`;
        }
        // Case 4: Generic fallback for active sales where installments might be paid but sale isn't 'paid_off' yet (e.g. other fees).
        else {
            const totalReceivableFormatted = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(this.totalReceivable());
            reminderMessage = `Mengingatkan mengenai tagihan Anda untuk kavling ${lotCode} dengan sisa total piutang sebesar ${totalReceivableFormatted}.`;
        }
      }
    }

    const fullMessage = `Halo Bapak/Ibu ${customerName}, kami dari ${companyName}.
${reminderMessage}

Mohon untuk segera melakukan pembayaran untuk kelancaran proses administrasi.
Terima kasih.`;

    const encodedMessage = encodeURIComponent(fullMessage);
    
    // Format phone number to international standard
    let phoneNumber = details.customer.phone.replace(/\D/g, '');
    if (phoneNumber.startsWith('0')) {
      phoneNumber = '62' + phoneNumber.substring(1);
    }
    
    return `https://api.whatsapp.com/send?phone=${phoneNumber}&text=${encodedMessage}`;
  });

  closeDetail() {
    this.close.emit();
  }
  
  promptCancelSale() {
      this.showCancelConfirmModal.set(true);
  }

  closeCancelModal() {
    this.showCancelConfirmModal.set(false);
  }

  confirmCancelSale() {
    this.dataService.cancelSale(this.saleId());
    this.showCancelConfirmModal.set(false);
    this.close.emit();
  }

  // Installment Payment
  openPaymentModal(installment: Installment) {
    if(installment.status !== 'paid') {
      this.paymentModalInstallment.set(installment);
    }
  }

  closePaymentModal() {
    this.paymentModalInstallment.set(null);
  }

  submitPayment() {
    const installment = this.paymentModalInstallment();
    if (installment) {
      const remainingAmount = installment.amount - installment.paid_amount;
      this.dataService.payInstallment(installment.id, remainingAmount);
      this.closePaymentModal();
    }
  }
  
  // DP Payment
  openDPPaymentModal() {
    this.dpPaymentAmount.set(this.sale()?.dp_sisa || 0);
    this.showDPPaymentModal.set(true);
  }
  
  closeDPPaymentModal() {
    this.showDPPaymentModal.set(false);
  }

  submitDPPayment() {
    const amount = this.dpPaymentAmount();
    if (amount > 0) {
      this.dataService.payDP(this.saleId(), amount);
      this.closeDPPaymentModal();
    }
  }

  // Flexible Payment
  onSaveFlexiblePayment(event: Event) {
    event.preventDefault();
    const amount = this.flexiblePaymentAmount();
    if (amount <= 0) {
      alert('Jumlah pembayaran harus lebih dari nol.');
      return;
    }
    this.dataService.makeFlexiblePayment(this.saleId(), amount, this.flexiblePaymentDate());
    // Reset form
    this.flexiblePaymentAmount.set(0);
    this.flexiblePaymentDate.set(new Date().toISOString().split('T')[0]);
  }

  printReceipt(payment: Payment) {
    const details = this.saleDetails();
    if (!details) return;

    this.paymentToPrint.set({
      company: this.dataService.companyProfile(),
      payment: payment,
      sale: details,
      customer: details.customer,
      lot: details.lot
    });
  }

  getStatusClass(status: InstallmentStatus) {
    switch (status) {
      case 'paid': return 'bg-green-100 text-green-800';
      case 'unpaid': return 'bg-gray-100 text-gray-800';
      case 'partial': return 'bg-yellow-100 text-yellow-800';
      case 'overdue': return 'bg-red-100 text-red-800';
      case 'cancelled': return 'bg-gray-200 text-gray-500';
      default: return 'bg-gray-100 text-gray-800';
    }
  }

  // --- Edit Sale Methods ---
  openEditModal() {
    // Deep copy to prevent modifying the original object during edit
    this.editableSale.set(JSON.parse(JSON.stringify(this.sale())));
    this.showEditModal.set(true);
  }

  closeEditModal() {
    this.showEditModal.set(false);
    this.editableSale.set(null);
  }
  
  handleSaleFormChange(field: keyof Omit<Sale, 'id' | 'invoice_no' | 'invoice_date' | 'kavling_id'>, value: string | number) {
      this.editableSale.update(sale => {
        if (!sale) return null;
        
        // Create a mutable copy
        const newSale: Sale = { ...sale, [field]: value };
  
        // Recalculate dependent fields
        if (['harga_dasar', 'promo_diskon'].includes(field as string)) {
          newSale.harga_netto = (newSale.harga_dasar || 0) - (newSale.promo_diskon || 0);
        }
        if (['harga_netto', 'uang_muka_persen'].includes(field as string)) {
           newSale.uang_muka_rp = (newSale.harga_netto * newSale.uang_muka_persen) / 100;
        }
        if (['harga_netto', 'biaya_ppjb', 'biaya_shm', 'biaya_lain_total'].includes(field as string)) {
           newSale.grand_total = (newSale.harga_netto || 0) + (newSale.biaya_ppjb || 0) + (newSale.biaya_shm || 0) + (newSale.biaya_lain_total || 0);
        }
        if (field === 'metode_id' && value === 'MP02') { // Cash Keras
          newSale.tenor = 0;
          newSale.uang_muka_persen = 100;
          newSale.uang_muka_rp = newSale.harga_netto;
        }
        
        newSale.dp_sisa = newSale.uang_muka_rp - newSale.dp_terbayar;
  
        return newSale;
      });
  }
  
  saveSale() {
    const saleToSave = this.editableSale();
    if (saleToSave) {
      const result = this.dataService.updateSale(saleToSave);
      if (result.success) {
        this.closeEditModal();
      } else {
        alert(result.message);
      }
    }
  }
}