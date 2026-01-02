import { Component, ChangeDetectionStrategy, computed, input, output } from '@angular/core';
import { CommonModule, CurrencyPipe, DatePipe } from '@angular/common';

@Component({
  selector: 'app-print-receipt',
  standalone: true,
  imports: [CommonModule, CurrencyPipe, DatePipe],
  templateUrl: './print-receipt.component.html',
  styleUrls: ['./print-receipt.component.css'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PrintReceiptComponent {
  paymentDetails = input.required<any>();
  close = output<void>();

  private terbilang(n: number): string {
    if (n < 0) return `Minus ${this.terbilang(Math.abs(n))}`;
    
    const satuan = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    
    let result = '';

    if (n < 12) {
      result = satuan[n];
    } else if (n < 20) {
      result = `${this.terbilang(n - 10)} Belas`;
    } else if (n < 100) {
      result = `${satuan[Math.floor(n / 10)]} Puluh ${this.terbilang(n % 10)}`;
    } else if (n < 200) {
      result = `Seratus ${this.terbilang(n - 100)}`;
    } else if (n < 1000) {
      result = `${satuan[Math.floor(n / 100)]} Ratus ${this.terbilang(n % 100)}`;
    } else if (n < 2000) {
      result = `Seribu ${this.terbilang(n - 1000)}`;
    } else if (n < 1000000) {
      result = `${this.terbilang(Math.floor(n / 1000))} Ribu ${this.terbilang(n % 1000)}`;
    } else if (n < 1000000000) {
      result = `${this.terbilang(Math.floor(n / 1000000))} Juta ${this.terbilang(n % 1000000)}`;
    } else if (n < 1000000000000) {
      result = `${this.terbilang(Math.floor(n / 1000000000))} Miliar ${this.terbilang(n % 1000000000)}`;
    } else if (n < 1000000000000000) {
      result = `${this.terbilang(Math.floor(n / 1000000000000))} Triliun ${this.terbilang(n % 1000000000000)}`;
    }

    return result.trim();
  }

  amountInWords = computed(() => {
    const amount = this.paymentDetails()?.payment?.amount || 0;
    if (amount === 0) return "Nol Rupiah";
    return `${this.terbilang(amount)} Rupiah`.replace(/\s\s+/g, ' ');
  });

  print() {
    const printContentElement = document.getElementById('receiptContent');
    if (!printContentElement) {
      console.error('Print content element with ID "receiptContent" not found!');
      return;
    }
    const printContent = printContentElement.outerHTML;
    
    // 1. Create a hidden iframe
    const iframe = document.createElement('iframe');
    iframe.style.position = 'absolute';
    iframe.style.left = '-9999px'; // Move it off-screen
    iframe.style.top = '-9999px';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    iframe.setAttribute('title', 'Print Frame');
    
    document.body.appendChild(iframe);
    
    const doc = iframe.contentWindow?.document;
    if (!doc) {
      console.error('Could not access iframe document.');
      document.body.removeChild(iframe);
      return;
    }

    // 2. Write the content into the iframe
    const allHeadContent = document.head.innerHTML;
    doc.open();
    doc.write(`
      <!DOCTYPE html>
      <html>
        <head>
          <title>Cetak Kuitansi</title>
          ${allHeadContent}
          <style>
            /* Additional styles for printing */
            @page {
              size: landscape; /* Force landscape orientation */
              margin: 10mm;    /* Provide a standard margin */
            }
            body {
              padding: 0;
              margin: 0;
              -webkit-print-color-adjust: exact !important; /* Force background colors/images for Chrome */
              color-adjust: exact !important; /* Standard property */
            }
          </style>
        </head>
        <body>
          ${printContent}
        </body>
      </html>
    `);
    doc.close();

    // 3. Print the iframe content
    setTimeout(() => {
      try {
        iframe.contentWindow?.focus(); // Focus is important for some browsers
        iframe.contentWindow?.print();
      } catch (e) {
        console.error("Printing failed:", e);
      } finally {
        // 4. Clean up: Remove the iframe after the print dialog is shown
        setTimeout(() => {
          if (iframe.parentNode) {
            iframe.parentNode.removeChild(iframe);
          }
        }, 500);
      }
    }, 500); // Wait for content and styles to be parsed
  }
}
