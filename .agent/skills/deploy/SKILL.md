---
name: Deploy Application
description: Deploy dan distribusi aplikasi SIMAK
---

# Deploy Application Skill

Skill untuk deploy dan distribusi aplikasi SIMAK.

## Pre-Deployment Checklist

### 1. Version Update
Update version di `package.json`:
```json
{
  "version": "1.x.x"
}
```

### 2. Environment Configuration
Pastikan `.env` production:
```env
APP_ENV=production
APP_DEBUG=false
```

### 3. Optimize Laravel
```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## Build for Distribution

### Build Secured EXE
```powershell
.\build-secured.ps1
```

### Output Location
- Installer: `dist/SIMAK-Setup-x.x.x.exe`
- Portable: `dist/win-unpacked/`

## Distribution Channels

### 1. Direct Download
Upload ke server:
- URL: `https://kavling.pro/downloads/`

### 2. Auto-Update (Electron)
Konfigurasi electron-updater di `electron-main.cjs`

## Post-Deployment

### Verify Installation
1. Download dan install di test machine
2. Cek license activation
3. Test core features
4. Verify database connectivity

### Rollback Plan
Simpan versi sebelumnya untuk rollback jika diperlukan.

## Server Deployment (API/Backend)
Jika deploy backend ke server:
```powershell
# SSH ke server dan pull latest
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```
