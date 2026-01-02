import { Component, ChangeDetectionStrategy, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DataService } from '../../services/data.service';
import { CompanyProfile } from '../../models/data.models';

@Component({
  selector: 'app-company-profile',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './company-profile.component.html',
  styles: [`
    @keyframes fade-in-out {
      0% { opacity: 0; transform: translateY(-20px); }
      10% { opacity: 1; transform: translateY(0); }
      90% { opacity: 1; transform: translateY(0); }
      100% { opacity: 0; transform: translateY(-20px); }
    }
    .animate-fade-in-out {
      animation: fade-in-out 3s ease-in-out forwards;
    }
  `],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CompanyProfileComponent implements OnInit {
  dataService = inject(DataService);
  profile = signal<CompanyProfile | null>(null);
  showSuccessMessage = signal(false);

  ngOnInit(): void {
    // Deep copy the profile to avoid mutating the original signal directly
    this.profile.set(JSON.parse(JSON.stringify(this.dataService.companyProfile())));
  }

  handleFormChange(field: keyof Omit<CompanyProfile, 'nomor_format'>, event: Event) {
    const value = (event.target as HTMLInputElement).value;
    this.profile.update(current => {
      if (!current) return null;
      return { ...current, [field]: value };
    });
  }
  
  handleNestedFormChange(parentField: 'nomor_format', childField: 'faktur' | 'kuitansi', event: Event) {
      const value = (event.target as HTMLInputElement).value;
      this.profile.update(current => {
          if (!current) return null;
          return {
              ...current,
              [parentField]: {
                  ...current[parentField],
                  [childField]: value
              }
          };
      });
  }

  onSave(event: Event) {
    event.preventDefault();
    if (this.profile()) {
      this.dataService.updateCompanyProfile(this.profile()!);
      this.showSuccessMessage.set(true);
      setTimeout(() => this.showSuccessMessage.set(false), 3000); // Hide after 3 seconds
    }
  }
}