import { Component, ChangeDetectionStrategy, computed, inject, signal, OnInit, OnDestroy, effect, ViewChild, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DataService } from './services/data.service';
import { ThemeService } from './services/theme.service';
import { LicenseService } from './services/license.service';

import { SidebarComponent } from './components/sidebar/sidebar.component';
import { DashboardComponent } from './components/dashboard/dashboard.component';
import { SalesComponent } from './components/sales/sales.component';
import { CompanyProfileComponent } from './components/company-profile/company-profile.component';
import { ProjectsComponent } from './components/projects/projects.component';
import { LotsComponent } from './components/lots/lots.component';
import { CustomersComponent } from './components/customers/customers.component';
import { SalesmenComponent } from './components/salesmen/salesmen.component';
import { DataManagementComponent } from './components/data-management/data-management.component';
import { LicenseGateComponent } from './components/license-gate/license-gate.component';


@Component({
  selector: 'app-root',
  standalone: true,
  imports: [
    CommonModule,
    SidebarComponent,
    DashboardComponent,
    SalesComponent,
    CompanyProfileComponent,
    ProjectsComponent,
    LotsComponent,
    CustomersComponent,
    SalesmenComponent,
    DataManagementComponent,
    LicenseGateComponent,
  ],
  templateUrl: './app.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: {
    '(window:beforeunload)': 'onBeforeUnload($event)'
  },
})
export class AppComponent implements OnInit, OnDestroy {
  dataService = inject(DataService);
  themeService = inject(ThemeService);
  licenseService = inject(LicenseService);
  isDarkMode = this.themeService.isDarkMode;

  @ViewChild('mainContent') mainContent!: ElementRef<HTMLElement>;
  @ViewChild(SalesComponent) salesComponent?: SalesComponent;

  activeView = signal('dashboard'); // dashboard, sales, projects, lots, customers, salesmen, company-profile, data-management
  showDisclaimer = signal(false);
  isSidebarOpen = signal(false);
  currentYear = new Date().getFullYear();
  currentDateTime = signal(''); // New signal for date and time

  private timerId: any; // Holder for setInterval ID

  needsInitialSetup = computed(() => !this.dataService.initialSetupDone());

  constructor() {
    effect(() => {
      const requestedView = this.dataService.requestedView();
      if (requestedView) {
        this.activeView.set(requestedView);
        this.dataService.requestedView.set(null); // Reset after handling
      }
    });

    // Effect to apply dark mode class to the document
    effect(() => {
      if (this.themeService.isDarkMode()) {
        document.documentElement.classList.add('dark');
      } else {
        document.documentElement.classList.remove('dark');
      }
    });
  }

  ngOnInit() {
    // Show disclaimer only once per session
    if (sessionStorage.getItem('disclaimerShown') !== 'true') {
      this.showDisclaimer.set(true);
    }

    // Start the clock
    this.updateDateTime();
    this.timerId = setInterval(() => this.updateDateTime(), 1000);
  }

  ngOnDestroy(): void {
    if (this.timerId) {
      clearInterval(this.timerId);
    }
  }

  private updateDateTime(): void {
    const now = new Date();
    const dateOptions: Intl.DateTimeFormatOptions = {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    };
    const timeOptions: Intl.DateTimeFormatOptions = {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: false,
    };
    const datePart = now.toLocaleDateString('id-ID', dateOptions);
    const timePart = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
    this.currentDateTime.set(`${datePart}, ${timePart}`);
  }

  dismissDisclaimer() {
    sessionStorage.setItem('disclaimerShown', 'true');
    this.showDisclaimer.set(false);
  }

  onViewChange(view: string) {
    if (view === 'sales') {
      this.salesComponent?.showSalesList();
    }

    this.activeView.set(view);
    this.closeSidebar();

    // Reset scroll position of the main content area
    if (this.mainContent?.nativeElement) {
      this.mainContent.nativeElement.scrollTop = 0;
    }
  }

  toggleSidebar() {
    this.isSidebarOpen.update(v => !v);
  }

  closeSidebar() {
    this.isSidebarOpen.set(false);
  }

  toggleTheme() {
    this.themeService.toggleTheme();
  }

  onBeforeUnload(event: BeforeUnloadEvent): void {
    if (this.dataService.initialSetupDone()) {
      // The custom message is not shown in modern browsers, but setting returnValue is necessary to trigger the prompt.
      const confirmationMessage = 'Perubahan mungkin tidak tersimpan. Pastikan Anda sudah melakukan backup data.';
      event.preventDefault(); // Standard for most browsers
      event.returnValue = confirmationMessage; // For older browsers
    }
  }
}
