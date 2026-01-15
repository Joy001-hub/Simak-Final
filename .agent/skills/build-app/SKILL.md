---
name: Build Application
description: Build aplikasi SIMAK sebagai Electron executable
---

# Build Application Skill

Skill untuk build aplikasi SIMAK menjadi executable Windows.

## Prerequisites
- Node.js terinstall
- Dependencies sudah diinstall (`npm install`)
- PHP dan Composer terinstall

## Build Steps

### 1. Clean Previous Build
```powershell
Remove-Item -Recurse -Force dist-electron -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force dist -ErrorAction SilentlyContinue
```

### 2. Install Dependencies
```powershell
npm install
composer install --optimize-autoloader --no-dev
```

### 3. Build Frontend
```powershell
npm run build
```

### 4. Build Electron App
```powershell
npm run build:electron
```

### 5. Build Secured (With ASAR Protection)
Gunakan script yang sudah ada:
```powershell
.\build-secured.ps1
```

## Output
Executable akan tersedia di folder `dist/` dengan nama sesuai package.json.

## Troubleshooting
- Jika build gagal, pastikan semua dependencies terinstall
- Cek file `electron-main.cjs` untuk konfigurasi Electron
- Pastikan `vite.config.js` sudah benar
