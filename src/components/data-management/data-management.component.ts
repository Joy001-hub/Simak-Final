import { Component, ChangeDetectionStrategy, inject, signal, ViewChild, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DataService } from '../../services/data.service';

@Component({
  selector: 'app-data-management',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './data-management.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DataManagementComponent {
  dataService = inject(DataService);

  @ViewChild('fileInput') fileInput!: ElementRef<HTMLInputElement>;

  showImportConfirmModal = signal(false);
  showResetConfirmModal = signal(false);
  showLoadDemoConfirmModal = signal(false);

  // --- Load Demo Data ---
  promptLoadDemoData() {
    this.showLoadDemoConfirmModal.set(true);
  }
  closeLoadDemoModal() {
    this.showLoadDemoConfirmModal.set(false);
  }
  confirmLoadDemoData() {
    this.dataService.loadDemoData();
    this.closeLoadDemoModal();
    alert('Data contoh berhasil dimuat ulang.');
  }

  // --- Export ---
  exportData() {
    this.dataService.exportDataAsJson();
  }

  // --- Import ---
  triggerImport() {
    this.showImportConfirmModal.set(true);
  }
  closeImportModal() {
    this.showImportConfirmModal.set(false);
  }
  confirmImport() {
    this.closeImportModal();
    this.fileInput.nativeElement.click();
  }
  onFileSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
      const file = input.files[0];
      const reader = new FileReader();
      reader.onload = (e) => {
        const text = e.target?.result as string;
        if (text) {
          this.dataService.importDataFromJson(text);
        } else {
          alert('Tidak dapat membaca file.');
        }
      };
      reader.onerror = () => {
        alert(`Gagal membaca file: ${reader.error}`);
      };
      reader.readAsText(file);
      input.value = '';
    }
  }

  // --- Reset ---
  promptReset() {
    this.showResetConfirmModal.set(true);
  }
  closeResetModal() {
    this.showResetConfirmModal.set(false);
  }
  confirmReset() {
    this.dataService.resetAllData();
    this.closeResetModal();
    alert('Seluruh data aplikasi telah berhasil direset.');
  }

}
