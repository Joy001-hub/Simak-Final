import { Component, ChangeDetectionStrategy, inject, signal, computed, WritableSignal } from '@angular/core';
import { CommonModule, CurrencyPipe } from '@angular/common';
import { DataService } from '../../services/data.service';
import { Lot } from '../../models/data.models';

@Component({
  selector: 'app-lots',
  standalone: true,
  imports: [CommonModule, CurrencyPipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './lots.component.html',
})
export class LotsComponent {
  dataService = inject(DataService);
  projects = this.dataService.projects;
  
  // Modal signals
  showModal = signal(false);
  currentLot = signal<Partial<Lot> | null>(null);
  showDeleteModal = signal(false);
  lotToDelete = signal<(Lot & { projectName: string; }) | null>(null);
  deleteError = signal<string | null>(null);

  // --- Filter State Signals ---
  filterProjectId = signal('');
  filterStatus = signal<'' | 'available' | 'sold'>('');

  // --- Sort State Signals ---
  sortColumn = signal<string>('id');
  sortDirection = signal<'asc' | 'desc'>('asc');

  // --- Computed Signal for Filtered and Sorted Lots ---
  filteredAndSortedLots = computed(() => {
    let lots = this.dataService.lotsWithProject();

    // Filtering
    const projectId = this.filterProjectId();
    if (projectId) {
      lots = lots.filter(lot => lot.project_id === +projectId);
    }
    const status = this.filterStatus();
    if (status) {
      lots = lots.filter(lot => lot.status === status);
    }

    // Sorting
    const column = this.sortColumn();
    const direction = this.sortDirection();
    
    type LotWithProject = (typeof lots)[0];

    return [...lots].sort((a: LotWithProject, b: LotWithProject) => {
      let valA: any;
      let valB: any;
      
      if (column === 'block_number') {
        // Pad lot number for correct alphanumeric sorting (e.g., A-1, A-2, A-10)
        valA = `${a.block}-${a.lot_number.toString().padStart(5, '0')}`;
        valB = `${b.block}-${b.lot_number.toString().padStart(5, '0')}`;
      } else {
        valA = a[column as keyof LotWithProject];
        valB = b[column as keyof LotWithProject];
      }

      let comparison = 0;
      if (valA > valB) {
        comparison = 1;
      } else if (valA < valB) {
        comparison = -1;
      }

      return direction === 'asc' ? comparison : -comparison;
    });
  });

  // --- Filter Methods ---
  onFilterChange(signal: WritableSignal<string>, event: Event) {
    const input = event.target as HTMLSelectElement;
    signal.set(input.value);
  }

  resetFilters() {
    this.filterProjectId.set('');
    this.filterStatus.set('');
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

  openModal(lot: Lot | null = null) {
    this.currentLot.set(lot ? { ...lot } : { project_id: 0, block: '', lot_number: '', area: 0, base_price: 0 });
    this.showModal.set(true);
  }

  closeModal() {
    this.showModal.set(false);
    this.currentLot.set(null);
  }

  updateField(field: keyof Omit<Lot, 'id' | 'status'>, event: Event, isNumber = false) {
    const target = event.target as HTMLInputElement | HTMLSelectElement;
    const value = isNumber ? parseInt(target.value, 10) || 0 : target.value;
    this.currentLot.update(l => l ? { ...l, [field]: value } : null);
  }

  saveLot() {
    const lot = this.currentLot();
    if (!lot || !lot.project_id || !lot.block || !lot.lot_number || lot.area <= 0 || lot.base_price <= 0) {
        alert('Please fill all fields with valid values.');
        return;
    }
    
    const saveData: Omit<Lot, 'id' | 'status'> = {
      project_id: lot.project_id,
      block: lot.block,
      lot_number: lot.lot_number,
      area: lot.area,
      base_price: lot.base_price,
    };

    if (lot.id) {
      this.dataService.updateLot({ ...saveData, id: lot.id, status: lot.status || 'available' });
    } else {
      this.dataService.addLot(saveData);
    }
    this.closeModal();
  }

  promptDelete(lot: Lot & { projectName: string; }) {
    this.lotToDelete.set(lot);
    this.deleteError.set(null);
    this.showDeleteModal.set(true);
  }

  closeDeleteModal() {
    this.showDeleteModal.set(false);
    this.lotToDelete.set(null);
    this.deleteError.set(null);
  }

  confirmDelete() {
    const lot = this.lotToDelete();
    if (lot) {
      const result = this.dataService.deleteLot(lot.id);
      if (result.success) {
        this.closeDeleteModal();
      } else {
        this.deleteError.set(result.message || 'An unknown error occurred.');
      }
    }
  }
}