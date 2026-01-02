import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LicenseService } from '../../services/license.service';

@Component({
    selector: 'app-license-gate',
    standalone: true,
    imports: [CommonModule],
    templateUrl: './license-gate.component.html',
    styleUrl: './license-gate.component.css',
})
export class LicenseGateComponent {
    licenseService = inject(LicenseService);

    // Form fields
    email = '';
    password = '';
    licenseKey = '';

    // UI state
    showPassword = signal(false);
    showResetForm = signal(false);
    isSubmitting = signal(false);

    togglePasswordVisibility(): void {
        this.showPassword.update((v) => !v);
    }

    async onActivate(event: Event): Promise<void> {
        event.preventDefault();

        if (!this.email || !this.password || !this.licenseKey) {
            return;
        }

        this.isSubmitting.set(true);

        const result = await this.licenseService.activate(
            this.email,
            this.password,
            this.licenseKey
        );

        this.isSubmitting.set(false);

        if (result.success) {
            // Clear form on success
            this.email = '';
            this.password = '';
            this.licenseKey = '';
        }
    }

    async onReset(event: Event): Promise<void> {
        event.preventDefault();
        await this.licenseService.reset();
        this.showResetForm.set(false);
    }

    getErrorAction(): string | null {
        const errorMessage = this.licenseService.errorMessage();
        if (!errorMessage) return null;
        return this.licenseService.getErrorAction(errorMessage);
    }
}
