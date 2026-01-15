---
name: Backup Database
description: Backup dan restore database SQLite SIMAK
---

# Backup Database Skill

Skill untuk backup dan restore database SQLite aplikasi SIMAK.

## Database Location
- Local database: `f:\simak-Ready-released\local` (SQLite)
- NeonDB sync: `f:\simak-Ready-released\neondb`

## Backup Commands

### Manual Backup
```powershell
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
Copy-Item "local" "storage/backups/local_$timestamp.db" -Force
```

### Create Backup Directory
```powershell
New-Item -ItemType Directory -Path "storage/backups" -Force
```

## Restore Commands

### Restore from Backup
```powershell
Copy-Item "storage/backups/[nama_backup].db" "local" -Force
```

## Laravel Backup (Via Artisan)

### Jika menggunakan spatie/laravel-backup
```powershell
php artisan backup:run --only-db
```

## Best Practices
1. Backup sebelum melakukan update major
2. Simpan backup di lokasi terpisah
3. Test restore secara berkala
4. Gunakan naming convention dengan timestamp

## Automated Backup Script
```powershell
# backup-db.ps1
$backupDir = "storage/backups"
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"

if (!(Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir -Force
}

Copy-Item "local" "$backupDir/local_$timestamp.db" -Force
Write-Host "Backup created: local_$timestamp.db"

# Keep only last 10 backups
Get-ChildItem $backupDir -Filter "local_*.db" | 
    Sort-Object LastWriteTime -Descending | 
    Select-Object -Skip 10 | 
    Remove-Item -Force
```
