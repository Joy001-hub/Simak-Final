import { Component, ChangeDetectionStrategy, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DataService } from '../../services/data.service';
import { Salesman } from '../../models/data.models';

@Component({
  selector: 'app-salesmen',
  standalone: true,
  imports: [CommonModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
   <div class="p-4 sm:p-10">
      <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8 gap-4">
        <h1 class="text-3xl lg:text-4xl font-bold text-gray-800 dark:text-gray-100">Manage Tim Marketing (Salesmen)</h1>
        <button (click)="openModal()" class="inline-flex items-center justify-center rounded-md bg-primary-900 px-5 py-2.5 text-base font-medium text-white shadow-sm hover:bg-primary-800 self-start sm:self-center">
          Add Salesman
        </button>
      </div>

      <div class="mb-4 flex flex-wrap items-center justify-between gap-3 text-sm text-gray-600 dark:text-gray-300">
        <div>
          Menampilkan {{ pagedSalesmen().length }} dari {{ totalSalesmen() }} data
        </div>
        <div class="flex items-center gap-2">
          <label for="salesmanPageSize" class="text-sm font-medium">Per halaman</label>
          <select id="salesmanPageSize" [value]="pageSize()" (change)="changePageSize($event)"
            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 px-2 py-1">
            <option [value]="10">10</option>
            <option [value]="20">20</option>
            <option [value]="50">50</option>
          </select>
        </div>
      </div>

      <!-- Desktop Table View -->
      <div class="hidden md:block bg-white dark:bg-gray-800 shadow-lg rounded-xl overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-4 text-left text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">ID</th>
              <th class="px-6 py-4 text-left text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Name</th>
              <th class="px-6 py-4 text-left text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Phone</th>
              <th class="px-6 py-4 text-right text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @for (salesman of pagedSalesmen(); track salesman.id) {
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-6 py-4 text-base font-medium text-gray-900 dark:text-gray-100">{{ salesman.id }}</td>
                <td class="px-6 py-4 text-base text-gray-600 dark:text-gray-300">{{ salesman.name }}</td>
                <td class="px-6 py-4 text-base text-gray-600 dark:text-gray-300">{{ salesman.phone }}</td>
                <td class="px-6 py-4 text-right text-base font-medium space-x-4">
                  <button (click)="openModal(salesman)" class="text-primary-800 dark:text-primary-400 hover:text-primary-900 dark:hover:text-primary-300 font-semibold">Edit</button>
                  <button (click)="promptDelete(salesman)" class="text-red-600 dark:text-red-500 hover:text-red-800 dark:hover:text-red-400 font-semibold">Delete</button>
                </td>
              </tr>
            } @empty {
              <tr>
                <td colspan="4" class="text-center py-10 text-gray-500 dark:text-gray-400">No salesmen found.</td>
              </tr>
            }
          </tbody>
        </table>
      </div>

      @if (totalSalesmen() > pageSize()) {
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm">
          <div class="text-gray-600 dark:text-gray-300">
            Halaman {{ pageIndex() }} dari {{ totalSalesmanPages() }}
          </div>
          <div class="flex items-center gap-2">
            <button (click)="prevPage()" [disabled]="pageIndex() === 1"
              class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-1 text-gray-700 dark:text-gray-200 disabled:opacity-50">
              Sebelumnya
            </button>
            <button (click)="nextPage()" [disabled]="pageIndex() === totalSalesmanPages()"
              class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-1 text-gray-700 dark:text-gray-200 disabled:opacity-50">
              Berikutnya
            </button>
          </div>
        </div>
      }

      <!-- Mobile Card View -->
      <div class="md:hidden space-y-4">
        @for (salesman of pagedSalesmen(); track salesman.id) {
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex justify-between items-start">
              <div>
                <p class="font-bold text-gray-800 dark:text-gray-100">{{ salesman.name }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ salesman.phone }}</p>
              </div>
              <p class="text-sm text-gray-500 dark:text-gray-400 font-mono">#{{ salesman.id }}</p>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-4">
              <button (click)="openModal(salesman)" class="text-primary-800 dark:text-primary-400 hover:text-primary-900 dark:hover:text-primary-300 font-semibold text-sm">Edit</button>
              <button (click)="promptDelete(salesman)" class="text-red-600 dark:text-red-500 hover:text-red-800 dark:hover:text-red-400 font-semibold text-sm">Delete</button>
            </div>
          </div>
        } @empty {
           <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center text-gray-500 dark:text-gray-400">
            No salesmen found.
          </div>
        }
      </div>
    </div>

    <!-- Add/Edit Modal -->
    @if (showModal()) {
      <div class="fixed inset-0 z-10 overflow-y-auto bg-gray-500 bg-opacity-75">
        <div class="flex min-h-full items-center justify-center p-4">
          <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl w-full max-w-lg">
            <div class="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
              <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ currentSalesman()?.id ? 'Edit' : 'Add' }} Salesman</h3>
              <div class="mt-5 space-y-5">
                <div>
                  <label class="block text-base font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                  <input type="text" [value]="currentSalesman()?.name || ''" (input)="updateField('name', $event)" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm sm:text-base px-3 py-2">
                </div>
                <div>
                  <label class="block text-base font-medium text-gray-700 dark:text-gray-300">Phone</label>
                  <input type="text" [value]="currentSalesman()?.phone || ''" (input)="updateField('phone', $event)" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm sm:text-base px-3 py-2">
                </div>
              </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
              <button type="button" (click)="saveSalesman()" class="inline-flex w-full justify-center rounded-md bg-primary-900 px-4 py-2 text-base font-medium text-white shadow-sm sm:ml-3 sm:w-auto">Save</button>
              <button type="button" (click)="closeModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-600 dark:text-gray-200 px-4 py-2 text-base font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 sm:mt-0 sm:w-auto">Cancel</button>
            </div>
          </div>
        </div>
      </div>
    }

    <!-- Delete Confirmation Modal -->
    @if (showDeleteModal() && salesmanToDelete(); as salesman) {
      <div class="fixed inset-0 z-20 overflow-y-auto bg-gray-500 bg-opacity-75">
        <div class="flex min-h-full items-center justify-center p-4">
          <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl w-full max-w-lg">
            <div class="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
              <div class="sm:flex sm:items-start">
                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                  <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.007H12v-.007z" /></svg>
                </div>
                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                  <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100">Delete Salesman</h3>
                  <div class="mt-2">
                    <p class="text-base text-gray-600 dark:text-gray-300">
                      Are you sure you want to delete the salesman "{{ salesman.name }}"? This action cannot be undone.
                    </p>
                  </div>
                   @if(deleteError()) {
                    <div class="mt-4 rounded-md bg-red-50 p-4">
                      <div class="flex">
                        <div class="flex-shrink-0">
                           <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                          </svg>
                        </div>
                        <div class="ml-3">
                          <h3 class="text-sm font-medium text-red-800">{{ deleteError() }}</h3>
                        </div>
                      </div>
                    </div>
                  }
                </div>
              </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
              <button type="button" (click)="confirmDelete()" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Delete</button>
              <button type="button" (click)="closeDeleteModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-600 dark:text-gray-200 px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 sm:mt-0 sm:w-auto">Cancel</button>
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class SalesmenComponent {
  dataService = inject(DataService);
  salesmen = this.dataService.salesmen;
  pageIndex = signal(1);
  pageSize = signal(20);
  totalSalesmen = computed(() => this.salesmen().length);
  totalSalesmanPages = computed(() => Math.max(1, Math.ceil(this.totalSalesmen() / this.pageSize())));
  pagedSalesmen = computed(() => {
    const start = (this.pageIndex() - 1) * this.pageSize();
    return this.salesmen().slice(start, start + this.pageSize());
  });

  showModal = signal(false);
  currentSalesman = signal<Partial<Salesman> | null>(null);

  showDeleteModal = signal(false);
  salesmanToDelete = signal<Salesman | null>(null);
  deleteError = signal<string | null>(null);

  openModal(salesman: Salesman | null = null) {
    this.currentSalesman.set(salesman ? { ...salesman } : { name: '', phone: '' });
    this.showModal.set(true);
  }

  closeModal() {
    this.showModal.set(false);
    this.currentSalesman.set(null);
  }

  updateField(field: keyof Omit<Salesman, 'id'>, event: Event) {
    const value = (event.target as HTMLInputElement).value;
    this.currentSalesman.update(s => s ? { ...s, [field]: value } : null);
  }

  saveSalesman() {
    const salesman = this.currentSalesman();
    if (!salesman || !salesman.name || !salesman.phone) {
        alert('Please fill all fields');
        return;
    }

    if (salesman.id) {
      this.dataService.updateSalesman(salesman as Salesman);
    } else {
      this.dataService.addSalesman({ name: salesman.name, phone: salesman.phone });
      this.pageIndex.set(this.totalSalesmanPages());
    }
    this.closeModal();
  }

  promptDelete(salesman: Salesman) {
    this.salesmanToDelete.set(salesman);
    this.deleteError.set(null);
    this.showDeleteModal.set(true);
  }

  closeDeleteModal() {
    this.showDeleteModal.set(false);
    this.salesmanToDelete.set(null);
    this.deleteError.set(null);
  }

  changePageSize(event: Event) {
    const target = event.target as HTMLSelectElement;
    const nextSize = parseInt(target.value, 10);
    this.pageSize.set(Number.isFinite(nextSize) ? nextSize : 20);
    this.pageIndex.set(1);
  }

  goToPage(page: number) {
    const nextPage = Math.min(Math.max(page, 1), this.totalSalesmanPages());
    this.pageIndex.set(nextPage);
  }

  prevPage() {
    this.goToPage(this.pageIndex() - 1);
  }

  nextPage() {
    this.goToPage(this.pageIndex() + 1);
  }

  confirmDelete() {
    const salesman = this.salesmanToDelete();
    if (salesman) {
      const result = this.dataService.deleteSalesman(salesman.id);
      if (result.success) {
        if (this.pageIndex() > this.totalSalesmanPages()) {
          this.pageIndex.set(this.totalSalesmanPages());
        }
        this.closeDeleteModal();
      } else {
        this.deleteError.set(result.message || 'An unknown error occurred.');
      }
    }
  }
}
