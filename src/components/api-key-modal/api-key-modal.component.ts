import { Component, ChangeDetectionStrategy, inject, signal, effect } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DataService } from '../../services/data.service';

@Component({
  selector: 'app-api-key-modal',
  standalone: true,
  imports: [CommonModule],
  template: `
    @if(dataService.showApiKeyModal()) {
      <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-500 bg-opacity-75 transition-opacity">
          <div class="flex min-h-full items-center justify-center p-4 text-center">
              <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                  <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                      <div class="sm:flex sm:items-start">
                          <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-blue-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672ZM12 2.25V4.5m5.834.166-1.591 1.591M21.75 12h-2.25m-7.5 0h7.5m-7.5 0-1.591 1.591M4.166 19.834 5.757 18.243M2.25 12H4.5m3.243-5.757L6.166 4.166" />
                              </svg>
                          </div>
                          <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                              <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">Kelola Kunci API Gemini</h3>
                              <div class="mt-2">
                                  <p class="text-sm text-gray-600">
                                      Fitur Proyeksi AI menggunakan Google Gemini. Silakan masukkan atau perbarui kunci API Google Gemini Anda.
                                  </p>
                                  <div class="mt-4">
                                      <label for="api-key" class="sr-only">Google Gemini API Key</label>
                                      <input type="password" id="api-key" [value]="tempApiKey()" (input)="tempApiKey.set($event.target.value)" placeholder="Masukkan API Key Anda di sini" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                                  </div>
                                  <p class="mt-2 text-xs text-gray-500">
                                    Kunci API Anda disimpan dengan aman hanya di browser ini dan tidak akan dibagikan.
                                    <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer" class="font-medium text-primary-800 hover:text-primary-700">
                                        Dapatkan API Key di Google AI Studio &rarr;
                                    </a>
                                  </p>
                              </div>
                          </div>
                      </div>
                  </div>
                  <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                      <button type="button" (click)="saveKey()" class="inline-flex w-full justify-center rounded-md bg-primary-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-800 sm:ml-3 sm:w-auto">Simpan</button>
                      <button type="button" (click)="closeModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Batal</button>
                  </div>
              </div>
          </div>
      </div>
    }
  `,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ApiKeyModalComponent {
  dataService = inject(DataService);
  tempApiKey = signal('');

  constructor() {
    effect(() => {
      if (this.dataService.showApiKeyModal()) {
        this.tempApiKey.set(this.dataService.geminiApiKey() || '');
      }
    });
  }

  closeModal() {
    this.dataService.showApiKeyModal.set(false);
    this.tempApiKey.set('');
  }

  saveKey() {
    const key = this.tempApiKey().trim();
    if (key) {
      this.dataService.setGeminiApiKey(key);
    } else {
      this.dataService.setGeminiApiKey(null);
    }
    this.closeModal();
  }
}
