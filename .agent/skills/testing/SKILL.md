---
name: Testing
description: Menjalankan test suite untuk aplikasi SIMAK
---

# Testing Skill

Skill untuk menjalankan berbagai jenis testing pada aplikasi SIMAK.

## Laravel Backend Tests

### Run All Tests
```powershell
php artisan test
```

### Run Specific Test File
```powershell
php artisan test --filter=NamaTestClass
```

### Run with Coverage
```powershell
php artisan test --coverage
```

## Frontend Tests (Jika ada)

### Run Vite Tests
```powershell
npm run test
```

## Database Testing

### Refresh Database for Testing
```powershell
php artisan migrate:fresh --seed --env=testing
```

## Manual Testing Checklist

### Core Features
- [ ] Login/Logout
- [ ] Dashboard loads correctly
- [ ] CRUD Kavling
- [ ] CRUD Projects
- [ ] Payment processing
- [ ] Report generation

### License System
- [ ] License activation
- [ ] License validation
- [ ] Offline mode

## Debugging Tips
- Check Laravel logs: `storage/logs/laravel.log`
- Enable debug mode in `.env`: `APP_DEBUG=true`
- Use `dd()` or `dump()` for quick debugging
