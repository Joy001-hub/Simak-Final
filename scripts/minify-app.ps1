# PHP Minifier Wrapper Script
# Integrates with pre-build process
# Run: .\scripts\minify-app.ps1

param(
    [switch]$EncodeStrings = $false,
    [switch]$DryRun = $false
)

$projectRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $projectRoot

Write-Host ""
Write-Host "========================================" -ForegroundColor Magenta
Write-Host "  SIMAK PRO - PHP Minifier" -ForegroundColor Magenta
Write-Host "========================================" -ForegroundColor Magenta
Write-Host ""

# Create backup first
$backupDir = "$projectRoot\app_backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
$tempMinified = "$projectRoot\app_minified_temp"

if ($DryRun) {
    Write-Host "[DRY RUN] Would backup app/ to $backupDir" -ForegroundColor Yellow
    Write-Host "[DRY RUN] Would minify PHP files" -ForegroundColor Yellow
    exit 0
}

# Step 1: Backup original app folder
Write-Host "[Step 1] Creating backup of app/ folder..." -ForegroundColor Cyan
Copy-Item -Path "$projectRoot\app" -Destination $backupDir -Recurse -Force
Write-Host "  Backup created: $backupDir" -ForegroundColor Green

# Step 2: Run minifier on app folder only
Write-Host ""
Write-Host "[Step 2] Minifying PHP files in app/ folder..." -ForegroundColor Cyan

$minifyArgs = @(
    "$projectRoot\scripts\minify-php.php",
    "$projectRoot\app",
    $tempMinified
)

if ($EncodeStrings) {
    $minifyArgs += "--encode-strings"
    Write-Host "  String encoding: ENABLED" -ForegroundColor Yellow
} else {
    Write-Host "  String encoding: DISABLED (safer)" -ForegroundColor Gray
}

php @minifyArgs

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "ERROR: Minification failed!" -ForegroundColor Red
    Write-Host "Restoring from backup..." -ForegroundColor Yellow
    Remove-Item -Path "$projectRoot\app" -Recurse -Force -ErrorAction SilentlyContinue
    Copy-Item -Path $backupDir -Destination "$projectRoot\app" -Recurse -Force
    Remove-Item -Path $tempMinified -Recurse -Force -ErrorAction SilentlyContinue
    exit 1
}

# Step 3: Replace original with minified
Write-Host ""
Write-Host "[Step 3] Replacing original files with minified versions..." -ForegroundColor Cyan
Remove-Item -Path "$projectRoot\app" -Recurse -Force
Move-Item -Path $tempMinified -Destination "$projectRoot\app"
Write-Host "  Done!" -ForegroundColor Green

# Step 4: Quick syntax check
Write-Host ""
Write-Host "[Step 4] Verifying PHP syntax..." -ForegroundColor Cyan

$hasError = $false
Get-ChildItem -Path "$projectRoot\app" -Recurse -Filter "*.php" | ForEach-Object {
    $result = php -l $_.FullName 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  [SYNTAX ERROR] $($_.Name)" -ForegroundColor Red
        $hasError = $true
    }
}

if ($hasError) {
    Write-Host ""
    Write-Host "WARNING: Some files have syntax errors!" -ForegroundColor Red
    Write-Host "Restoring from backup..." -ForegroundColor Yellow
    Remove-Item -Path "$projectRoot\app" -Recurse -Force
    Copy-Item -Path $backupDir -Destination "$projectRoot\app" -Recurse -Force
    exit 1
}

Write-Host "  All files passed syntax check!" -ForegroundColor Green

Write-Host ""
Write-Host "========================================" -ForegroundColor Magenta
Write-Host "  Minification Complete!" -ForegroundColor Magenta
Write-Host "========================================" -ForegroundColor Magenta
Write-Host ""
Write-Host "Backup saved at: $backupDir" -ForegroundColor Cyan
Write-Host ""
Write-Host "IMPORTANT: Test the application before running native:build" -ForegroundColor Yellow
Write-Host ""
