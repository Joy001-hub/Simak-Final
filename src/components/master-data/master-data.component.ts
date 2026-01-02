// Fix: Implementing the missing MasterDataComponent with an inline template.
import { Component, ChangeDetectionStrategy, inject, signal } from '@angular/core';
import { CommonModule, CurrencyPipe } from '@angular/common';
import { DataService } from '../../services/data.service';

@Component({
  selector: 'app-master-data',
  standalone: true,
  imports: [CommonModule, CurrencyPipe],
  template: `
    <div class="p-6 bg-gray-50 dark:bg-gray-900 min-h-full">
      <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-6">Master Data</h1>

      <div class="mb-6">
        <div class="border-b border-gray-200 dark:border-gray-700">
          <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button
              (click)="setTab('projects')"
              [class]="activeTab() === 'projects' ? 'border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'"
              class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
              Projects
            </button>
            <button
              (click)="setTab('lots')"
              [class]="activeTab() === 'lots' ? 'border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'"
              class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
              Lots
            </button>
            <button
              (click)="setTab('customers')"
              [class]="activeTab() === 'customers' ? 'border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'"
              class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
              Customers
            </button>
            <button
              (click)="setTab('salesmen')"
              [class]="activeTab() === 'salesmen' ? 'border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'"
              class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
              Salesmen
            </button>
          </nav>
        </div>
      </div>

      <!-- Projects Table -->
      @if (activeTab() === 'projects') {
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Project Name</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Location</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              @for (project of projects(); track project.id) {
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ project.id }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ project.name }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ project.location }}</td>
                </tr>
              }
            </tbody>
          </table>
        </div>
      }

      <!-- Lots Table -->
      @if (activeTab() === 'lots') {
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Project</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Block/Number</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Area (m²)</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Base Price</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              @for (lot of lots(); track lot.id) {
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ lot.id }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ lot.projectName }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ lot.block }}/{{ lot.lot_number }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ lot.area }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ lot.base_price | currency:'IDR':'symbol':'1.0-0' }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <span 
                      class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                      [class]="lot.status === 'available' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300'">
                      {{ lot.status }}
                    </span>
                  </td>
                </tr>
              }
            </tbody>
          </table>
        </div>
      }

      <!-- Customers Table -->
      @if (activeTab() === 'customers') {
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Phone</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Address</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              @for (customer of customers(); track customer.id) {
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ customer.id }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ customer.name }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ customer.phone }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ customer.address }}</td>
                </tr>
              }
            </tbody>
          </table>
        </div>
      }

      <!-- Salesmen Table -->
      @if (activeTab() === 'salesmen') {
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Phone</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              @for (salesman of salesmen(); track salesman.id) {
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ salesman.id }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ salesman.name }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ salesman.phone }}</td>
                </tr>
              }
            </tbody>
          </table>
        </div>
      }
    </div>
  `,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class MasterDataComponent {
  dataService = inject(DataService);
  activeTab = signal('projects'); // projects, lots, customers, salesmen

  projects = this.dataService.projects;
  lots = this.dataService.lotsWithProject;
  customers = this.dataService.customers;
  salesmen = this.dataService.salesmen;

  setTab(tab: string) {
    this.activeTab.set(tab);
  }
}