import { Injectable, signal } from '@angular/core';

@Injectable({
  providedIn: 'root',
})
export class ThemeService {
  isDarkMode = signal<boolean>(false);

  constructor() {
    // Check for saved theme in localStorage or user's system preference
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedTheme) {
      this.isDarkMode.set(savedTheme === 'dark');
    } else {
      this.isDarkMode.set(prefersDark);
    }
  }

  toggleTheme(): void {
    this.isDarkMode.update(isDark => {
      const newMode = !isDark;
      localStorage.setItem('theme', newMode ? 'dark' : 'light');
      return newMode;
    });
  }
}
