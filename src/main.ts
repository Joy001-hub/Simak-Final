import 'zone.js';
import { bootstrapApplication } from '@angular/platform-browser';
import { AppComponent } from './app.component';
import './styles.css';

// Ensure JIT compiler is available when the dev server runs without AOT.
import '@angular/compiler';

bootstrapApplication(AppComponent).catch((err) => console.error(err));
